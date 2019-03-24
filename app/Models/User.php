<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;

use DB;
use Log;
use Cache;
use Storage;
use Carbon\Carbon;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use HasRoles;

    protected $guard_name = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'register_source_id',
        'register_source_type',
        'email',
        'mobile',
        'password',
        'name',
        'blacklist',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getGuardNameAttribute(){
        return (new self())->guard_name;
    }

    public function getAvatarAttribute($avatar){
        if(!empty($avatar)){
            $avatar = @json_decode($avatar, true);
            if($avatar){
                if(!empty($avatar['data_url'])){
                    $avatar = $avatar['data_url'];
                }elseif(!empty($avatar['disk']) && !empty($avatar['path'])){
                    $avatar = Storage::disk($avatar['disk'])->url($avatar['path']);
                }
            }
        }
        if(empty($avatar)){
            $wechatUsers = $this->wechatUsers()->get();
            foreach($wechatUsers as $wechatUser){
                if($wechatUser->avatar){
                    $avatar = $wechatUser->avatar;
                    break;
                }
            }
        }
        return $avatar;
    }

    public function getDistanceAttribute(){
        return empty($this->distance) ? '' : round($this->distance/1000, 2) . 'km';
    }

    public function getSecretPhone($query = []){
        $phone = $this->mobile;

        $secretPhone = $phone;
        if($phone){
            $expireAfterMinutes = 10;
            $cacheKey = "secret_phone:{$phone}";
            $secretPhone = Cache::remember($cacheKey, $expireAfterMinutes, function() use($query, $phone, $expireAfterMinutes){
                $data = config('aliyun.product.pls');
                $data['options']['query']['PhoneNoA'] = $phone;
                $data['options']['query']['Expiration'] = Carbon::now()->addMinutes($expireAfterMinutes + 1);
                foreach($query as $queryKey=>$queryValue){
                    $data['options']['query'][$queryKey] = $queryValue;
                }
                try{
                    $aliyunApp = app("aliyun");
                    $response = clone $aliyunApp;
                    foreach($data as $methodName=>$methodParams){
                        $response = is_null($methodParams) ? $response->$methodName() : $response->$methodName($methodParams);
                    }
                    if(config('aliyun.log.level') == 'debug'){
                        Log::channel('aliyun_daily')->info(json_encode($data), $response->toArray());
                    }
                    return array_get($response, 'SecretBindDTO.SecretNo');
                }catch(\Exception $e){
                    Log::channel('aliyun_daily')->error((string) $e);
                }
            });
            if(empty($secretPhone)){
                $secretPhone = $phone;
                Cache::forget($cacheKey);
            }
        }

        return $secretPhone;
    }

    public function setPasswordAttribute($value){
        $this->attributes['password'] = bcrypt(trim($value));
    }

    /**
     * 获得用户注册来源的模型。
     */
    public function register_source()
    {
        return $this->morphTo();
    }
    public function registerSource()
    {
        return $this->register_source();
    }

    public function wechatUsers(){
        return $this->hasMany(WechatUser::class);
    }

    public function roleInformation($roleName = ''){
        $model = $this->hasMany(UserRoleInformation::class);
        if($roleName){
            $role = self::getRole($roleName);
            if($role){
                $model->where('role_id', $role->id);
            }
        }
        return $model;
    }

    public function roleAttributes($roleName = ''){
        $model = $this->belongsToMany(RoleAttribute::class);
        if($roleName){
            $role = self::getRole($roleName);
            if($role){
                $model->whereIn('role_attribute_id', RoleAttribute::where('role_id', $role)->pluck('id')->toArray());
            }
        }
        return $model;
    }

    public function feedback(){
        return $this->hasMany(Feedback::class);
    }

    public function announcements(){
        return $this->belongsToMany(Announcement::class);
    }

    public function notices(){
        return $this->hasMany(Notice::class);
    }

    // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'guard_name'    => $this->guard_name,
            'username'      => $this->username,
        ];
    }

    static function generateUserName($prefix = ''){
        $username = config('prefix.user');
        $username .= $prefix ?: '';
        $username .= Str::uuid();
        return $username;
    }

    static function getRole($roleName){
        return Role::where([
            'name'       => $roleName,
            'guard_name' => (new self())->guard_name,
        ])->first();
    }

    public function getAnnouncements(){
        $user = $this;
        $announcements = Announcement::orderBy('id', 'desc')->get();
        $notReadAnnouncementIds = Announcement::whereDoesntHave('users', function($query) use($user){
            $query->where('user_id', $user->id);
        })->orderBy('id', 'desc')->pluck('id')->toArray();
        return $announcements->map(function($announcement) use($user, $notReadAnnouncementIds){
            $announcement->has_read = !in_array($announcement->id, $notReadAnnouncementIds);
            return $announcement;
        });
    }

    static function getDistanceFieldSql($longitude = null, $latitude = null){
        return $longitude && $latitude ? DB::raw("ST_Distance_Sphere(point(users.longitude, users.latitude), point({$longitude}, {$latitude})) * .000621371192 AS distance") : DB::raw('0 AS distance');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use Storage;
use Image;

use DB;

class Base extends Model
{
    const STATE_UNPAID = 'unpaid';
    const STATE_PAID = 'paid';

    public static function storageEncrypt($storageInfo, $option = [])
    {
        $url = Storage::disk($storageInfo['disk'])->url($storageInfo['path']);
        if (
            config('image.rewrite')
            && isset($option['image'])
        ) {
            $width = array_get($option, 'image.width', 750);
            $height = array_get($option, 'image.height');
            if (
                $width
                || $height
            ) {
                $url = route('image', [
                    'url' => $url,
                    'w' => $width,
                    'h' => $height,
                ]);
            }
        }
        return [
            // 'raw'              => $storageInfo,
            'encryptedData'    => encrypt($storageInfo),
            'url'              => $url,
        ];
    }

    public function getJson($value)
    {
        return $value ? json_decode($value, true) : null;
    }

    public function setJson($value, $field = '')
    {
        $value = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        if ($field) {
            $this->attributes[$field] = $value;
        } else {
            return $value;
        }
    }

    public function getStateTextAttribute()
    {
        return array_get($this->getStates(), "{$this->state_value}.text", '');
    }

    public function getPayStateValueAttribute()
    {
        if ($this->paid_at) {
            return self::STATE_PAID;
        } else {
            return self::STATE_UNPAID;
        }
    }

    public function getPayStateTextAttribute()
    {
        return array_get($this->getPayStates(), "{$this->pay_state_value}.text", '');
    }

    public static function getPayStates()
    {
        return [
            self::STATE_UNPAID => [
                'value' => self::STATE_UNPAID,
                'text'  => '未付款',
            ],
            self::STATE_PAID => [
                'value' => self::STATE_PAID,
                'text'  => '已付款',
            ],
        ];
    }

    public static function stateOptions()
    {
        return array_values(self::getStates());
    }

    public static function payStateOptions()
    {
        return array_values(self::getPayStates());
    }

    public static function errorRollBack($errorMessage = '执行出错')
    {
        DB::rollback();
        return [
            'error_message' => $errorMessage,
        ];
    }

    public static function getSystemOperator($message = '')
    {
        return [
            'user' => [
                'name'      => '系统',
                'username'  => 'system',
            ],
            'message' => $message,
        ];
    }

    public static function allinpayParamsFilter($params, $generateSign = true)
    {
        foreach ($params as $paramField=>$paramValue) {
            if (is_null($paramValue) || $paramValue == '') {
                unset($params[$paramField]);
            }
        }
        if ($generateSign) {
            $params['sign'] = self::allinpayGenerateSign($params);
        }
        return $params;
    }

    public static function allinpayGenerateSign($params)
    {
        $params = $params;
        $params['key'] = config('allinpay.md5_sign_key');
        ksort($params);

        $sign = '';
        foreach ($params as $paramField=>$paramValue) {
            $sign .= "&{$paramField}={$paramValue}";
        }
        $sign = trim($sign, '&');
        $sign = strtoupper(md5($sign));
        return $sign;
    }

    public static function modelFilter($model, $filter = [])
    {
        $lastDays = array_get($filter, 'last_days');
        if (!is_null($lastDays)) {
            $model->whereBetween('created_at', [
                now()->subDays($lastDays-1)->format('Y-m-d 00:00:00'),
                now(),
            ]);
        }
        return $model;
    }

    public static function getStorageConfig($type)
    {
        return array_get(config('upload.storage'), $type, []);
    }

    public static function saveImageFromUrl($imageUrl, $storageConfig = [])
    {
        $storageDisk = array_get($storageConfig, 'disk', 'public');
        // $imageResponse = app('http_client')->get($imageUrl);
        // $imageResponse->getStatusCode() == 200
        // $image = Image::make($imageResponse->getBody()->getContents());
        $image = Image::make($imageUrl);
        $subPath = array_get($storageConfig, 'sub_path', 'images');
        $fileName = array_get($storageConfig, 'file_name', (string) Str::uuid());
        $format = array_get($storageConfig, 'format', 'png');
        $storage = Storage::disk($storageDisk);
        $filePathArray = is_array($subPath) ? $subPath : [
            $subPath
        ];
        if (array_get($storageConfig, 'sub_date', true)) {
            $filePathArray[] = now()->format('Y/m/d');
        }
        $filePathArray[] = "{$fileName}.{$format}";
        $filePath = implode('/', $filePathArray);
        $storage->put($filePath, $image->encode($format));
        // $image->save($storage->path($filePath));
        return [
            'disk' => $storageDisk,
            'path' => $filePath,
        ];
    }

    public static function uploadImageFromUrl($imageUrl, $type)
    {
        $storageInfo = Base::saveImageFromUrl($imageUrl, $type);
        return Base::storageEncrypt($storageInfo);
    }
}

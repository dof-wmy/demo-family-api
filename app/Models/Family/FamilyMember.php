<?php

namespace App\Models\Family;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;
use Carbon\Carbon;

class FamilyMember extends Model
{
    use SoftDeletes, NodeTrait;

    protected $fillable = [
        'family_id',
        'name',
        'sex',
        'birthday',
        'father_id',
        'mother_id',
    ];

    public function setBirthdayAttribute($value){
        $this->attributes['birthday'] = Carbon::parse($value)->format('Y-m-d');
    }

    public function setFatherIdAttribute($value){
        $this->attributes['father_id'] = $value;
    }

    public function family(){
        return $this->belongsTo(Family::class);
    }
    public function father(){
        return $this->belongsTo(self::class, 'father_id')->where([
            'sex' => 1,
        ]);
    }
    public function mother(){
        return $this->belongsTo(self::class, 'mother_id')->where([
            'sex' => 2,
        ]);
    }

    public function getLftName()
    {
        return 'left';
    }

    public function getRgtName()
    {
        return 'right';
    }

    public function getParentIdName()
    {
        return 'father_id';
    }

    // Specify parent id attribute mutator
    public function setParentAttribute($value)
    {
        $this->setFatherIdAttribute($value);
    }

    public function getHusband(){
        $familyMember = $this;
        if(
            $familyMember->sex === 2
            && empty($familyMember->father_id)
        ){
            $husbandId = FamilyMember::where([
                'mother_id' => $familyMember->id,
            ])->whereNotNull('father_id')->value('father_id');
            if($husbandId){
                $husband = FamilyMember::find($husbandId);
            }
        }
        $husband = !empty($husband) && $husband->sex === 1 ? $husband : null;
        $familyMember->husband = $husband;
        return $husband;
    }

    public function getFamilyRelationShip(FamilyMember $theOtherFamilyMember){
        $familyMember = $this;
        $familyMember->getHusband();
        $theOtherFamilyMember->getHusband();

        $familyRelation = '';
        $comparableFamilyMember = $familyMember->husband ?: $familyMember;
        $theOtherComparableFamilyMember = $theOtherFamilyMember->husband ?: $theOtherFamilyMember;
        // TODO 区分女媳和堂亲（父系社会不考虑表亲关系）
        if(
            $familyMember->sex !== $theOtherFamilyMember->sex
            && ($comparableFamilyMember->id === $theOtherComparableFamilyMember->id)
        ){
            // 判断夫妻关系
            $familyRelation = $familyMember->husband ? '妻子' : '丈夫';
        }elseif(
            $theOtherComparableFamilyMember->father_id === $comparableFamilyMember->id
            || $theOtherComparableFamilyMember->mother_id === $comparableFamilyMember->id
        ){
            // 判断父母关系
            $familyRelation = $familyMember->sex === 1 ? '父亲' : '母亲';
        }elseif(
            $comparableFamilyMember->father_id === $theOtherComparableFamilyMember->id
            || $comparableFamilyMember->mother_id === $theOtherComparableFamilyMember->id
        ){
            // 判断子女关系
            $familyRelation = $familyMember->sex === 1 ? '儿子' : '女儿';
        }else{
            $comparableFamilyMember = FamilyMember::withDepth()->find($comparableFamilyMember->id);
            $theOtherComparableFamilyMember = FamilyMember::withDepth()->find($theOtherComparableFamilyMember->id);

            $ancestorsAndSelf = FamilyMember::defaultOrder()->ancestorsAndSelf($comparableFamilyMember->id);
            $firstAncestor = $ancestorsAndSelf ? $ancestorsAndSelf->first() : null;
            if(
                $firstAncestor
                && $firstAncestor->isAncestorOf($theOtherComparableFamilyMember)
            ){
                // 能匹配到其他亲属关系
                $depthDifference = $comparableFamilyMember->depth - $theOtherComparableFamilyMember->depth;
                if($depthDifference === 0){
                    // 平辈
                    if($comparableFamilyMember->birthday < $theOtherComparableFamilyMember->birthday){
                        $familyRelation = $familyMember->sex === 1 ? '哥哥' : '姐姐';
                    }else{
                        $familyRelation = $familyMember->sex === 1 ? '弟弟' : '妹妹';
                    }
                }elseif($depthDifference === -1){
                    // 长一辈
                    $familyRelation = $familyMember->sex === 1 ? 'uncle' : 'aunt';
                }elseif($depthDifference < -1){
                    // 长两辈及以上 great
                    $familyRelation = $familyMember->sex === 1 ? 'grandfather' : 'grandmother';
                }elseif($depthDifference === 1){
                    // 晚一辈
                    $familyRelation = $familyMember->sex === 1 ? 'nephew' : 'niece';
                }elseif($depthDifference > 1){
                    // 晚两辈及以上 great
                    $familyRelation = $familyMember->sex === 1 ? 'grandson' : 'granddaughter';
                }else{
                    // 不可能存在的情况
                }
            }
        }

        if(empty($familyRelation)){
            return [
                'messages' => [
                    "暂时匹配不到{$familyMember->name} 和 {$theOtherFamilyMember->name} 的关系",
                ],
            ];
        }

        $inverseFamilyRelation = self::getInverseFamilyRelationShip($familyRelation, $theOtherFamilyMember->sex);
        return [
            'messages' => [
                "{$theOtherFamilyMember->name} 叫 {$familyMember->name} 作 {$familyRelation}",
                "{$familyMember->name} 叫 {$theOtherFamilyMember->name} 作 {$inverseFamilyRelation}",
            ],
        ];
    }

    public function getInverseFamilyRelationShip($familyRelation, $sex){
        return array_get([
            '丈夫' => [
                '2' => '妻子',
            ],
            '妻子' => [
                '1' => '丈夫',
            ],
            '父亲' => [
                '1' => '儿子',
                '2' => '女儿',
            ],
            '母亲' => [
                '1' => '儿子',
                '2' => '女儿',
            ],
            '儿子' => [
                '1' => '父亲',
                '2' => '母亲',
            ],
            '女儿' => [
                '1' => '父亲',
                '2' => '母亲',
            ],
            '哥哥' => [
                '1' => '弟弟',
                '2' => '妹妹',
            ],
            '姐姐' => [
                '1' => '弟弟',
                '2' => '妹妹',
            ],
            '弟弟' => [
                '1' => '哥哥',
                '2' => '姐姐',
            ],
            '妹妹' => [
                '1' => '哥哥',
                '2' => '姐姐',
            ],
            'uncle' => [
                '1' => 'nephew',
                '2' => 'niece',
            ],
            'aunt' => [
                '1' => 'nephew',
                '2' => 'niece',
            ],
            'nephew' => [
                '1' => 'uncle',
                '2' => 'aunt',
            ],
            'niece' => [
                '1' => 'uncle',
                '2' => 'aunt',
            ],
            'grandfather' => [
                '1' => 'grandson',
                '2' => 'granddaughter',
            ],
            'grandmother' => [
                '1' => 'grandson',
                '2' => 'granddaughter',
            ],
            'grandson' => [
                '1' => 'grandfather',
                '2' => 'grandmother',
            ],
            'granddaughter' => [
                '1' => 'grandfather',
                '2' => 'grandmother',
            ],
        ], "{$familyRelation}.{$sex}", '');
    }

    public static function getSexes(){
        return [
            [
                'value' => 1,
                'text' => '男',
            ],
            [
                'value' => 2,
                'text' => '女',
            ],
        ];
    }

    public static function availableSexes(){
        return array_pluck(self::getSexes(), 'value');
    }
}

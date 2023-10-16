<?php


namespace app\api\logic;


use think\Cache;

class OpenLiveLogic
{

    public static function getVideoList($lotteryType, $year, $page=1){
        $ck = 'getVideoList-'.$lotteryType.'_'.$year.'_'.$page;
        if(Cache::has($ck)){
            return Cache::get($ck);
        }
        $url = CaijiLogic::WEBSITE.'unite49/h5/lottery/video/list?lotteryType='.$lotteryType.'&year='.$year.'&pageSize=10&pageNum='.$page;
        $res = curl_get_https($url);
        if($res){
            $arr = json_decode($res, true);
            if($arr && isset($arr['data']['recordList'])){
                Cache::set($ck, $arr['data']['recordList'], 300);
                return $arr['data']['recordList'];
            }else{
                return [];
            }
        }else{
            return [];
        }
    }

    public static function getDetail($id, $lotteryType){
        $ck = 'huifang_getDetail'.$id.'_'.$lotteryType;
        if(Cache::has($ck)){
            return Cache::get($ck);
        }
        $url = CaijiLogic::WEBSITE.'unite49/h5/lottery/video/list?lotteryType='.$lotteryType.'&year='.$year.'&pageSize=10&pageNum='.$page;
        $res = curl_get_https($url);
        if($res){
            $arr = json_decode($res, true);
            if($arr && isset($arr['data']['recordList'])){
                Cache::set($ck, $arr['data']['recordList'], 300);
                return $arr['data']['recordList'];
            }else{
                return [];
            }
        }else{
            return [];
        }
    }

}
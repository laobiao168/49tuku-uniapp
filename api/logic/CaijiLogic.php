<?php


namespace app\api\logic;


use think\Db;
use function Sodium\add;

class CaijiLogic
{

    const WEBSITE = 'https://49208.com/';

    public static function kaijiangxianchang(){
        //澳门
        $url = self::WEBSITE.'unite49/h5/index/lastLotteryRecord?lotteryType=2';
        $string = curl_post_https($url, '');
        $obj = json_decode($string, true);
        $data = [];
        $data['display'] = $obj['data']['display'];
        $data['videoUrl'] = $obj['data']['videoUrl'];
        $data['data'] = json_encode($obj['data']['recommendList']);
        $data['lotteryType'] = 2;
        Db::name('caiji_kjxc')->where(['lotteryType'=>2])->update($data);
        //香港
        $url = self::WEBSITE.'/unite49/h5/index/lastLotteryRecord?lotteryType=1';
        $string = curl_post_https($url, '');
        $obj = json_decode($string, true);
        $data = [];
        $data['display'] = $obj['data']['display'];
        $data['videoUrl'] = $obj['data']['videoUrl'];
        $data['data'] = json_encode($obj['data']['recommendList']);
        $data['lotteryType'] = 1;
        Db::name('caiji_kjxc')->where(['lotteryType'=>1])->update($data);
        echo "\n采集开奖现场-九肖十码完成...";
    }

    //采集综合资料
    public static function corpusList(){
        $msg = '';
        $user = Db::name('user')->where(['group_id'=>2])->select();
        $ulen = count($user)-1;
        $typelist = Db::name('caiji_articletype')->order('ctime asc')->limit(10)->select();
        foreach ($typelist as $type){
            $url = self::WEBSITE.'unite49/h5/article/search?type=4&articleTypeId='.$type['articleTypeId'].'&pageNum=1&pageSize=10';
            $string = curl_get_https($url);
            $obj = json_decode($string, true);
            if($obj && isset($obj['data']['list'])){
                $addlist = [];
                foreach ($obj['data']['list'] as $item){
                    $has = Db::name('caiji_articles')->where(['articleId'=>$item['articleId']])->find();
                    if(!$has){
                        $urlinfo = self::WEBSITE.'unite49/h5/article/detail?articleId='.$item['articleId'];
                        $stringinfo = curl_get_https($urlinfo);
                        $detail = json_decode($stringinfo, true);
                        if($detail && isset($detail['data'])){
                            $vo = [];
                            $vo['articleId'] = $item['articleId'];
                            $vo['articleTypeId'] = $type['articleTypeId'];
                            $vo['clickCount'] = $item['clickCount'];
                            $vo['commentCount'] = $item['commentCount'];
                            $vo['title'] = $item['title'];
                            $vo['content'] = $detail['data']['description'];
                            $vo['addtime'] = time();
                            $vo['user_id'] = $user[rand(0, $ulen)]['id'];
                            $addlist[] = $vo;
                        }
                    }
                }
                if($addlist){
                    Db::name('caiji_articles')->insertAll($addlist);
                    $msg.= "\n采集".$type['articleTypeName'].'='.count($addlist).'个资料';
                }
            }
            Db::name('caiji_articletype')->where(['articleTypeId'=>$type['articleTypeId']])->setInc('ctime');
        }

        echo $msg;
    }
}
<?php


namespace app\api\controller;


use app\api\logic\CaijiLogic;
use app\api\logic\KaijiangLogic;
use think\Controller;
use think\Db;
use think\Exception;
use think\Log;

/**
 * 采集
 */
class Caiji extends Controller
{
    //3秒任务
    public function second3(){
        try{
            $kj = new KaijiangLogic();
            $kj->xinaomen();
            $kj->aomen();
            $kj->taiwan();
            $kj->xinjiapo();
            $kj->xianggang();
        }catch (Exception $ex){
            $msg = $ex->getMessage().';'.$ex->getFile().';'.$ex->getLine();
            Log::error('minute1错误'.$msg);
            echo $msg;
        }
    }

    //1分钟任务
    public function minute1(){
        try{
            CaijiLogic::corpusList();
        }catch (Exception $ex){
            $msg = $ex->getMessage().';'.$ex->getFile().';'.$ex->getLine();
            Log::error('minute1错误'.$msg);
            echo $msg;
        }
    }
    //5分钟任务
    public function minute5(){
        try{
            CaijiLogic::corpusList();
            CaijiLogic::kaijiangxianchang();
        }catch (Exception $ex){
            $msg = $ex->getMessage().';'.$ex->getFile().';'.$ex->getLine();
            Log::error('minute5错误'.$msg);
            echo $msg;
        }
    }


    public function init(){
        fuck(2);
        $url = 'https://49208.com/unite49/h5/article/listArticleType?type=4&lotteryType=2';
        $res = curl_get_https($url);
        $arr = json_decode($res, true);
        $data = $arr['data']['list'];
        $list = [];
        $sort = 999;
        foreach ($data as $item){
            $vo = [];
            $vo['articleTypeId'] = $item['articleTypeId'];
            $vo['articleTypeName'] = $item['articleTypeName'];
            $vo['lotteryType'] = $item['lotteryType'];
            $vo['sort'] = $sort;
            $list[] = $vo;
            $sort --;
        }
        //Db::name('caiji_articletype')->insertAll($list);

    }


}
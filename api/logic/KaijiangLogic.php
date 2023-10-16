<?php


namespace app\api\logic;


use think\Db;

class KaijiangLogic
{

    private $ball_list = ["01", "02", "03", "04","05",'06', "07", "08","09","10","11", "12", "13","14","15","16","17", "18", "19","20","21","22", "23", "24","25","26","27","28", "29", "30","31","32","33", "34", "35","36","37","38","39", "40","41","42","43","44" ,"45", "46", "47", "48", "49"];

    private $tip = ['全','网','正','在','开','奖','中'];

    public function xinaomen(){
        $kjurl = 'https://www.macaumarksix.com/api/live2';
        $res = file_get_contents($kjurl);
        $kjarr = json_decode($res, true);
        $data['expect'] = $kjarr[0]['expect'];
        $data['expectNext'] = $data['expect']+1;
        $data['nextTime'] = date('Y-m-d H:i:s', strtotime($kjarr[0]['openTime'])+86400);
        $data['num'] = explode(',', $kjarr[0]['openCode']);
        $data['sx'] = explode(',', $kjarr[0]['zodiac']);
        $data['color'] = explode(',', $kjarr[0]['wave']);
        $wx = [];
        foreach ($data['num'] as $i=>&$num){
            if(!in_array($num, $this->ball_list)){
                $num = $this->tip[$i];
            }
            $wx[] = $this->getnum($num)['wx'];
        }
        $data['wx'] = $wx;
        $data['info'] = '六合论坛搭建www.sharecy.net';
        Db::name('dd')->where(['key'=>'amqs'])->update(['value'=>str_replace(date('Y'), '', $data['expectNext'])]);
        Db::name('dd')->where(['key'=>'amopentime'])->update(['value'=>strtotime($data['nextTime'])]);
        file_put_contents(ROOT_PATH.'/public/kj/xinaomen.json', json_encode($data, JSON_UNESCAPED_UNICODE));
        echo 'ok';
    }

    public function aomen(){
        $kjurl = 'https://www.macaumarksix.com/api/live';
        $res = file_get_contents($kjurl);
        $kjarr = json_decode($res, true);
        $data['expect'] = $kjarr[0]['expect'];
        $data['expectNext'] = $data['expect']+1;
        $data['nextTime'] = date('Y-m-d H:i:s', strtotime($kjarr[0]['openTime'])+86400);
        $data['num'] = explode(',', $kjarr[0]['openCode']);
        $data['sx'] = explode(',', $kjarr[0]['zodiac']);
        $data['color'] = explode(',', $kjarr[0]['wave']);
        $wx = [];
        foreach ($data['num'] as $i=>&$num){
            if(!in_array($num, $this->ball_list)){
                $num = $this->tip[$i];
            }
            $wx[] = $this->getnum($num)['wx'];
        }
        $data['wx'] = $wx;
        $data['info'] = '六合论坛搭建www.sharecy.net';
        Db::name('dd')->where(['key'=>'lamqs'])->update(['value'=>str_replace(date('Y'), '', $data['expectNext'])]);
        Db::name('dd')->where(['key'=>'lamopentime'])->update(['value'=>strtotime($data['nextTime'])]);
        file_put_contents(ROOT_PATH.'/public/kj/aomen.json', json_encode($data, JSON_UNESCAPED_UNICODE));
        echo 'ok';
    }

    public function xianggang(){
        $kjurl = 'https://zhibo3.yuexiawang.com:777/js/i1i1i1i1i1l1l1l1l0.js?_='.time();
        $res = file_get_contents($kjurl);
        $obj = json_decode($res, true);
        $kjarr = explode(',', $obj['k']);
        $data['expect'] = $kjarr[0];
        $data['expectNext'] = $kjarr[8];
        $data['nextTime'] = date('Y').'-'.$kjarr[9].'-'.$kjarr[10]. ' 21:33:00';
        $data['num'] = [];
        $data['wx'] = [];
        $data['sx'] = [];
        $data['color'] = [];
        for($i = 0; $i<7; $i++){
            $num = $kjarr[$i+1];
            if(!in_array($num, $this->ball_list)){
                $num = $this->tip[$i];
            }
            $data['num'][] = $num;
            $binfo = $this->getnum($num);
            $data['wx'][] = $binfo['wx'];
            $data['color'][] = $binfo['bs'];
            $data['sx'][] = $this->shengxiao($num);
        }
        $data['info'] = '六合论坛搭建www.sharecy.net';
        Db::name('dd')->where(['key'=>'xgqs'])->update(['value'=>str_replace(date('Y'), '', $data['expectNext'])]);
        Db::name('dd')->where(['key'=>'xgopentime'])->update(['value'=>strtotime($data['nextTime'])]);
        file_put_contents(ROOT_PATH.'/public/kj/hk.json', json_encode($data, JSON_UNESCAPED_UNICODE));
        echo 'ok';
    }


    public function taiwan(){
        $kjurl = 'https://zhibo2.yuexiawang.com:777/js/i1i1i1i1i1l1l1l1l0.js?_='.time();
        $res = file_get_contents($kjurl);
        $obj = json_decode($res, true);
        $kjarr = explode(',', $obj['k']);
        $data['expect'] = $kjarr[0];
        $data['expectNext'] = $kjarr[8];
        $data['nextTime'] = date('Y').'-'.$kjarr[9].'-'.$kjarr[10]. ' 20:50:00';
        $data['num'] = [];
        $data['wx'] = [];
        $data['sx'] = [];
        $data['color'] = [];
        for($i = 0; $i<7; $i++){
            $num = $kjarr[$i+1];
            if(!in_array($num, $this->ball_list)){
                $num = $this->tip[$i];
            }
            $data['num'][] = $num;
            $binfo = $this->getnum($num);
            $data['wx'][] = $binfo['wx'];
            $data['color'][] = $binfo['bs'];
            $data['sx'][] = $this->shengxiao($num);
        }
        $data['info'] = '六合论坛搭建www.sharecy.net';
        Db::name('dd')->where(['key'=>'twqs'])->update(['value'=>str_replace(date('Y'), '', $data['expectNext'])]);
        Db::name('dd')->where(['key'=>'twopentime'])->update(['value'=>strtotime($data['nextTime'])]);
        file_put_contents(ROOT_PATH.'/public/kj/tw.json', json_encode($data, JSON_UNESCAPED_UNICODE));
        echo 'ok';
    }

    public function xinjiapo(){
        $kjurl = 'https://zhibo4.yuexiawang.com:777/js/i1i1i1i1i1l1l1l1l0.js?_='.time();
        $res = file_get_contents($kjurl);
        $obj = json_decode($res, true);
        $kjarr = explode(',', $obj['k']);
        $data['expect'] = $kjarr[0];
        $data['expectNext'] = $kjarr[8];
        $data['nextTime'] = date('Y').'-'.$kjarr[9].'-'.$kjarr[10]. ' 18:40:00';
        $data['num'] = [];
        $data['wx'] = [];
        $data['sx'] = [];
        $data['color'] = [];
        for($i = 0; $i<7; $i++){
            $num = $kjarr[$i+1];
            if(!in_array($num, $this->ball_list)){
                $num = $this->tip[$i];
            }
            $data['num'][] = $num;
            $binfo = $this->getnum($num);
            $data['wx'][] = $binfo['wx'];
            $data['color'][] = $binfo['bs'];
            $data['sx'][] = $this->shengxiao($num);
        }
        $data['info'] = '六合论坛搭建www.sharecy.net';
        Db::name('dd')->where(['key'=>'xjpqs'])->update(['value'=>str_replace(date('Y'), '', $data['expectNext'])]);
        Db::name('dd')->where(['key'=>'xjpopentime'])->update(['value'=>strtotime($data['nextTime'])]);
        file_put_contents(ROOT_PATH.'/public/kj/xinjiapo.json', json_encode($data, JSON_UNESCAPED_UNICODE));
        echo 'ok';
    }


    private function shengxiao($ball){
        $sx_hou = array('08','20','32','44');
        $sx_yang = array('09','21','33','45');
        $sx_ma = array('10','22','34','46');
        $sx_she = array('11','23','35','47');
        $sx_long = array('12','24','36','48');
        $sx_tu = array('01','13','25','37','49');
        $sx_hu = array('02','14','26','38');
        $sx_niu = array('03','15','27','39');
        $sx_shu = array('04','16','28','40');
        $sx_zhu = array('05','17','29','41');
        $sx_gou = array('06','18','30','42');
        $sx_ji = array('07','19','31','43');
        if(in_array($ball, $sx_hou)){
            return '猴';
        }
        if(in_array($ball, $sx_yang)){
            return '羊';
        }
        if(in_array($ball, $sx_shu)){
            return '鼠';
        }
        if(in_array($ball, $sx_niu)){
            return '牛';
        }
        if(in_array($ball, $sx_hu)){
            return '虎';
        }
        if(in_array($ball, $sx_tu)){
            return '兔';
        }
        if(in_array($ball, $sx_long)){
            return '龙';
        }
        if(in_array($ball, $sx_she)){
            return '蛇';
        }
        if(in_array($ball, $sx_ma)){
            return '马';
        }
        if(in_array($ball, $sx_ji)){
            return '鸡';
        }
        if(in_array($ball, $sx_gou)){
            return '狗';
        }
        if(in_array($ball, $sx_zhu)){
            return '猪';
        }
        return '';
    }

    private function getnum($num){
        //开奖号码处理
        $ball_r = array("01", "02", "07", "08", "12", "13", "18", "19", "23", "24", "29", "30", "34", "35", "40", "45", "46");
        $ball_b = array("03", "04", "09", "10", "14", "15", "20", "25", "26", "31", "36", "37", "41", "42", "47", "48");
        $ball_g = array("05", "06", "11", "16", "17", "21", "22", "27", "28", "32", "33", "38", "39", "43", "44", "49");
        $wuxin_j = array('01', '02', '09', '10', '23', '24', '31', '32', '39', '40');
        $wuxin_m = array('05', '06', '13', '14', '21', '22', '35', '36', '43', '44');
        $wuxin_s = array('11', '12', '19', '20', '27', '28', '41', '42', '49');
        $wuxin_h = array('07', '08', '15', '16', '29', '30', '37', '38', '45', '46');
        $wuxin_t = array('03', '04', '17', '18', '25', '26', '33', '34', '47', '48');

        $vo = array();
        if(in_array($num, $ball_r)){
            $vo['bs'] = 'red';
        }elseif(in_array($num, $ball_b)){
            $vo['bs'] = 'blue';
        }else{
            $vo['bs'] = 'green';
        }
        if(in_array($num, $wuxin_j)){
            $vo['wx'] = '金';
        }elseif(in_array($num, $wuxin_m)){
            $vo['wx'] = '木';
        }elseif(in_array($num, $wuxin_s)){
            $vo['wx'] = '水';
        }elseif(in_array($num, $wuxin_h)){
            $vo['wx'] = '火';
        }elseif(in_array($num, $wuxin_t)){
            $vo['wx'] = '土';
        }else{
            $vo['wx'] = '';
        }
        return $vo;
    }


}
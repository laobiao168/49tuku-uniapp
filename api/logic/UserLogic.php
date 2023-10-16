<?php


namespace app\api\logic;


use think\Db;

class UserLogic
{

    public static function getUserInfo($uid, $myid=0){
        $user = Db::name('user')->field(['id', 'username', 'nickname', 'avatar', 'score', 'num_huozan','num_fans','num_focus','num_article'])->where(['id'=>$uid])->find();
        if($user){
            if($uid == $myid){
                $user['myself'] = 1;
            }else{
                $user['myself'] = 0;
            }
            //是否关注
            $user['isfocus'] = 0;
            if($myid>0){
                $fans = Db::name('fans')->where(['user_id'=>$uid, 'fans_id'=>$myid])->find();
                if($fans){
                    $user['isfocus'] = 1;
                }
            }
            if($user['avatar']==''){
                $user['avatar'] = letter_avatar($user['username']);
            }
        }
        return $user;
    }

}
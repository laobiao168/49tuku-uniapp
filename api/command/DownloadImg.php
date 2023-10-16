<?php
namespace app\api\command;


use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\Exception;

class DownloadImg extends Command
{

    protected function configure()
    {
        $this->setName('downimg')->setDescription('download 49 picture');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('start..');
        $amurl = Config::get('tuku.amhb');
        $xgurl = Config::get('tuku.xgct');
        $twurl = Config::get('tuku.twct');
        $xjpurl = Config::get('tuku.xjpct');
        try {
            while (1){
                $dd = Db::name('dd')->column('value', 'key');
                //下载澳门
                $tu = Db::name('tu_aomen')->where(['lastdown'=>['neq', $dd['amqs']]])->find();
                if($tu){
                    $qi = intval($dd['amqs']);
                    if($tu['color']==1){
                        $tu['pic'] = $amurl. 'col' .'/' .$qi . '/' .$tu['pictureUrl'];
                    }else{
                        $tu['pic'] = $amurl. 'black'. '/' .$qi . '/' .$tu['pictureUrl'];
                    }
                    $local = dlfile($tu, $qi, 2);
                    $size = filesize($local);
                    if($size>1000){
                        $output->writeln($local.' ;   '.$size);
                        Db::name('tu_aomen')->where(['id'=>$tu['id']])->update(['lastdown'=>$dd['amqs']]);
                    }
                }

                //下载香港
                $tu = Db::name('tu_xianggang')->where(['lastdown'=>['neq', $dd['xgqs']]])->find();
                if($tu){
                    $qi = intval($dd['xgqs']);
                    if($tu['color']==1){
                        $tu['pic'] = $xgurl. 'col' .'/' .$qi . '/' .$tu['pictureUrl'];
                    }else{
                        $tu['pic'] = $xgurl. 'black'. '/' .$qi . '/' .$tu['pictureUrl'];
                    }
                    $local = dlfile($tu, $qi, 1);
                    $size = filesize($local);
                    if($size>1000){
                        $output->writeln($local.' ;   '.$size);
                        Db::name('tu_xianggang')->where(['id'=>$tu['id']])->update(['lastdown'=>$dd['xgqs']]);
                    }
                }

                //下载台湾
                $tu = Db::name('tu_taiwan')->where(['lastdown'=>['neq', $dd['twqs']]])->find();
                if($tu){
                    $qi = intval($dd['twqs']);
                    if($tu['color']==1){
                        $tu['pic'] = $twurl. 'col' .'/' .$qi . '/' .$tu['pictureUrl'];
                    }else{
                        $tu['pic'] = $twurl. 'black'. '/' .$qi . '/' .$tu['pictureUrl'];
                    }
                    $local = dlfile($tu, $qi, 3);
                    $size = filesize($local);
                    if($size>1000){
                        $output->writeln($local.' ;   '.$size);
                        Db::name('tu_taiwan')->where(['id'=>$tu['id']])->update(['lastdown'=>$dd['twqs']]);
                    }
                }

                //下载新加坡
                $tu = Db::name('tu_xinjiapo')->where(['lastdown'=>['neq', $dd['xjpqs']]])->find();
                if($tu){
                    $qi = intval($dd['xjpqs']);
                    if($tu['color']==1){
                        $tu['pic'] = $xjpurl. 'col' .'/' .$qi . '/' .$tu['pictureUrl'];
                    }else{
                        $tu['pic'] = $xjpurl. 'black'. '/' .$qi . '/' .$tu['pictureUrl'];
                    }
                    $local = dlfile($tu, $qi, 4);
                    $size = filesize($local);
                    if($size>1000){
                        $output->writeln($local.' ;   '.$size);
                        Db::name('tu_xinjiapo')->where(['id'=>$tu['id']])->update(['lastdown'=>$dd['xjpqs']]);
                    }
                }
                sleep(1);
            }
        }catch (Exception $ex){
            $output->writeln($ex->getMessage().';'.$ex->getLine());
        }
    }

}
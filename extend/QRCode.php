<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: QRCode.php
 * | Description: 生成二维码接口
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-05-07 19:26
 * | Email: equinoxsun@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\index\model;
use think\Model;
use think\Validate;
use Endroid\QrCode\Writer\PngDataUriWriter;

error_reporting(0);

class QRCode extends Model
{
    /**
     * 创建二维码
     * @param  integer $object_id [类型id，目前只有商品]
     * @param  string  $label     [二维码下面文字，包括大小视情况而改]
     * @return [type]             [description]
     */
    public function createQRCode($object_id=0,$label='')
    {
        if (!$object_id) {
           return '';
        }
        $url = config('extconfig.domain.mobile_domain')."/index.html#/want_goods_details/".$object_id;
        $image_url='QrCode_'.$object_id.'_'.str_replace(".","",microtime(true)).'.png';
        $file_path =$_SERVER['DOCUMENT_ROOT'].'/uploads/QrCode/'.$image_url;
        //生成当前的二维码
        $qrCode = new \Endroid\QrCode\QrCode();
        $qrCode
            ->setText($url)
            ->setSize(300)
            ->setMargin(10)
            ->setEncoding('UTF-8')
            ->setErrorCorrectionLevel('high')
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
            ->setLabel($label, 8, null, 'center')
            ->setLabelFontSize(14)
            //->setLabel($label, 8, 'static/fonts/Arial.ttf', 'center')
            //->setLabelFontSize(16)
            //->setValidateResult(true)
            ->writeFile($file_path);

        if ($qrCode->getWriterByPath($file_path)) {
            return $image_url;
        }
    }

    /**
     * 生成二维码图片，无缓存
     * @param  integer $object_id [description]
     * @param  string  $label     [description]
     * @return [type]             [description]
     */
    public function createQRCodeImg($object_id=0,$label='')
    {
        if (!$object_id) {
           return '';
        }
        $this->_configs = config("extconfig");
        $url = $this->_configs['domain']['mobile_domain']."/index.html#/want_goods_details/".$object_id;
        // $url = urlencode($url);

        //生成当前的二维码
        $qrCode = new \Endroid\QrCode\QrCode();
        $qrCode
            ->setText($url)
            ->setSize(300)
            ->setMargin(10)
            ->setEncoding('UTF-8')
            ->setErrorCorrectionLevel('quartile')
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0));
            // ->setLabel($label, 8, null, 'center')
            // ->setLabelFontSize(14);
            //->setValidateResult(true)
        $pngDataUriData = $qrCode->writeString(PngDataUriWriter::class);
        return $pngDataUriData;
    }
}
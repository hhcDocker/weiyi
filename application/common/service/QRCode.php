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

namespace app\common\service;
use think\Validate;
use Endroid\QrCode\Writer\PngDataUriWriter;

error_reporting(0);

class QRCode
{
    /**
     * 生成二维码图片，无缓存
     * @param  integer $url [description]
     * @return [type]       [description]
     */
    public function createQRCodeImg($url='')
    {
        if (!$url) {
           return '';
        }
        $url = $_SERVER['HTTP_HOST'].'/'.$url;
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

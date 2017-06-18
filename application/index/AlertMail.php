<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: AlertMail.php
 * | Description: send an email to developer when API occurs errors
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-06-05 21:03
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\index;

class AlertMail {
    public $content = null;
    public function __construct($content){
        $this->content = $content;
    }
    public function send(){
        if(config("alert_turn_on") && !empty($this->content)){
            $mail = new \PHPMailer;
            $mail->CharSet = "UTF-8";
            $mail->isSMTP();
            $mail->Host = 'smtp.purplethunder.cn';
            $mail->SMTPAuth = true;
            $mail->Username = 'alert@purplethunder.cn';
            $mail->Password = 'Zl.bj@2017';
            $mail->Port = 587;
            $mail->setFrom('alert@purplethunder.cn', 'Alert');
            $mail->addAddress('equinoxsun@purplethunder.cn', 'ganhuola');
            $mail->isHTML(true);
            $subject = config("alert_mail_subject");
            if(empty($subject)) {
                $subject = "Alert";
            }
            $mail->Subject = $subject;
            $mail->Body = $this->content;
            $mail->send();
        }
    }
}

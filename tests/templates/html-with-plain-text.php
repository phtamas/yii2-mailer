<?php
/* @var $this \yii\base\View */
/* @var $mail \phtamas\yii2\mailer\Mail */
/* @var $dynamicData string */


$mail = $this->context;
?>
<p>
    Html body with dynamic data: <?= $dynamicData ?>
</p>
<?php $mail->beginPlainTextBody() ?>
With a plain text body too. <?= $dynamicData ?>
<?php $mail->endPlainTextBody() ?>
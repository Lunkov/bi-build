<?php
class Mail {

  public static function check_address($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
  }


  public static function send($email_from, $email_to, $subject, $content) {
   $headers  = "From: $email_from <$email_from>\r\n";
   $headers .= "Content-Type: text/html;charset=\"windows-1251\"\n";
   $headers .= "Content-Transfer-Encoding: 8bit\r\n";
   $message = $content."\r\n";//message body whats else???
   
   mail($email_to, $subject, $message, $headers);
  }

  public function send_attachment($mailto, $from_mail, $from_name, $subject, $message, $path, $filename) {
      $file = $path.$filename;
      $file_size = filesize($file);
      $handle = fopen($file, 'r');
      $content = fread($handle, $file_size);
      fclose($handle);
      $content = chunk_split(base64_encode($content));
      $uid = md5(uniqid(time()));
      $name = basename($file);
      $header = "From: $from_name <$from_mail>\r\n";
      $header .= "Reply-To: $mailto\r\n";
      $header .= "MIME-Version: 1.0\r\n";
      $header .= "Content-Type: multipart/mixed; boundary=\"$uid\"\r\n\r\n";
      $header .= "This is a multi-part message in MIME format.\r\n";
      $header .= "--$uid\r\n";
      $header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
      $header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
      $header .= $message."\r\n\r\n";
      $header .= "--$uid\r\n";
      $header .= "Content-Type: application/octet-stream; name=\"$filename\"\r\n"; // use diff. tyoes here
      $header .= "Content-Transfer-Encoding: base64\r\n";
      $header .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
      $header .= $content."\r\n\r\n";
      $header .= "--$uid--";
      if (mail($mailto, $subject, "", $header)) {
          echo "mail send ... OK\n"; // or use booleans here
      } else {
          echo "mail send ... ERROR!\n";
      }
  }

}

?>

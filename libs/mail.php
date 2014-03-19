<?
class Mail {

  public function check_address($email) {
    // First, we check that there's one @ symbol, 
    // and that the lengths are right.
    if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
      // Email invalid because wrong number of characters 
      // in one section or wrong number of @ symbols.
      return false;
    }
    // Split it into sections to make life easier
    $email_array = explode('@', $email);
    $local_array = explode('.', $email_array[0]);
    for ($i = 0; $i < sizeof($local_array); $i++) {
      if(!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&?'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) {
        return false;
      }
    }
    // Check if domain is IP. If not, 
    // it should be valid domain name
    if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
      $domain_array = explode(".", $email_array[1]);
      if (sizeof($domain_array) < 2) {
          return false; // Not enough parts to domain
      }
      for ($i = 0; $i < sizeof($domain_array); $i++) {
        if(!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|?([A-Za-z0-9]+))$", $domain_array[$i])) {
          return false;
        }
      }
    }
    return true;
  }


  public function send($email_from, $email_to, $subject, $content) {
   $headers  = "From: $email_from <$email_from>\r\n";
   $headers .= "Content-Type: text/html;charset=\"windows-1251\"\n";
   $headers .= "Content-Transfer-Encoding: 8bit\r\n";
   $message = $content."\r\n";//message body whats else???
   
   mail($email_to, $subject, $message, $headers);
  }

  public function send_attachment($mailto, $from_mail, $from_name, $replyto, $subject, $message, $path, $filename) {
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

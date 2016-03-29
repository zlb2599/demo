<?php 

class canpointSendmail 
{ 
/* 
* 메일발송을 위한 클래스 
* 외부 SMTP 서버를 지원합니다. 
* Author: Gwangsoo, Ryu (piver@ineemail.com) 
*/ 

protected $UseSMTPServer = false; // 다른 SMTP 서버를 이용할 경우 
protected $SMTPServer = "smtp.exmail.qq.com"; // SMTP 서버 도메인 
protected $SMTPPort = 25; // Port 
protected $SMTPAuthUser="service@canpoint.net"; // SMTP 인증 사용자 
protected $SMTPAuthPasswd="qp123&*"; // SMTP 인증 비밀번호 
protected $Socket; 

protected $MailHeaderArray = array(); // 메일헤더를 담을 배열 

protected $MailFrom; // 보내는 사람 
protected $ReplyTo; // 회신받을 주소 (기본적으로 보내는 메일주소가 된다) 
protected $MailTo = array(); // 받는 사람을 담을 배열 

protected $Subject; // 메일제목 
protected $MailBody; // 메일본문 
protected $Charset = 'UTF8'; // 메일기본 캐릭터셋 
protected $Attach = array(); // 인코딩된 첨부파일 

protected $Boundary; // Bound 

public function __construct($charset = 'UTF8') 
{ 
$this->Boundary = md5(uniqid(microtime())); // 바운드를 초기화한다 
if(!empty($charset)) $this->Charset = $charset; // 캐릭터셋 
} 

public function setFrom($email, $name = null) 
{ 
// 보내는 메일 
$this->setReplyTo($email); 
return $this->MailFrom = ($name) ? $name . ' <' . $email . '>' : $email; 
} 

public function setReplyTo($email) 
{ 
// 회신주소 - 기본적으로 보내는 메일을 회신주소로 셋한다 
return $this->ReplyTo = $email; 
} 

public function setSubject($Subject) 
{ 
// 제목 
return $this->Subject = $Subject; 
} 

public function addTo($email, $name = null) 
{ 
// 받는 메일을 추가한다 
return $this->MailTo[$email] = $name; 
} 

public function addAttach($Filename, $Source) 
{ 
// 첨부파일을 추가한다 
$fp = fopen($Source, 'r'); // 소스파일을 연다 
if($fp) { 
$fBody = fread($fp, filesize($Source)); // 파일의 내용을 읽어온다 
@fclose($fp); 

$this->Attach[$Filename] = $fBody; // Attach 배열에 담는다 
} 
} 

public function setMailBody($Body, $useHtml = true) 
{ 
if(!$useHtml) { // 메일본문이 HTML 형식이 아니면 HTML 형식으로 바꾸어준다 
$Body = ' 
<html> 
<head> 
<meta http-equiv="Content-Type" content="text/html; charset=' . $this->Charset . '">
 <style type="text/css"> 
BODY, TH, TD, DIV, SPAN, P, INPUT { 
font-size:12px; 
line-height:17px; 
} 
BODY, DIV { text-align:justify; } 
</style> 
</head> 

<body> 
' . nl2br($Body) . ' 
</body> 
</html> 
'; 
} 

$this->MailBody = $Body; // 메일본문을 셋한다 
} 

protected function AddBasicHeader() 
{ 
// 메일의 기본 헤더를 작성한다 
$this->addHeader('From', $this->MailFrom); 
$this->addHeader('User-Agent', 'Dabuilder Mail System'); 
$this->addHeader('X-Accept-Language', 'zh-ch, en'); 
$this->addHeader('X-Sender', $this->ReplyTo); 
$this->addHeader('X-Mailer', 'PHP'); 
$this->addHeader('X-Priority', 1); 
$this->addHeader('Reply-to', $this->ReplyTo); 
$this->addHeader('Return-Path', $this->ReplyTo); 

if(count($this->Attach) > 0) { // 첨부파일이 있을 경우의 헤더 
$this->addHeader('MIME-Version', '1.0'); 
$this->addHeader('Content-Type', 'Multipart/mixed; boundary = "' . $this->Boundary . '"');
 } else { // 첨부파일이 없는 일반 메일일 경우의 헤더 
$this->addHeader('Content-Type', 'text/html; charset=' . $this->Charset); 
$this->addHeader('Content-Transfer-Encoding', '8bit'); 
} 
} 

protected function addHeader($Content, $Value) 
{ 
// 메일헤더의 내용을 추가한다 
$this->MailHeaderArray[$Content] = $Value; 
} 

protected function MailAttach() 
{ 
// 첨부파일이 있을 경우 메일본문에 첨부파일을 덧붙인다 
$arrRet = array(); 

if(count($this->Attach) > 0) { 
foreach($this->Attach as $Filename => $fBody) { 
$tmpAttach = "--" . $this->Boundary . "\r\n"; 
$tmpAttach .= "Content-Type: application/octet-stream\r\n"; 
$tmpAttach .= "Content-Transfer-Encoding: base64\r\n"; 
$tmpAttach .= "Content-Disposition: attachment; filename=\"" . $Filename . "\"\r\n\r\n";
 $tmpAttach .= $this->encodingContents($fBody) . "\r\n\r\n"; 

$arrRet[] = $tmpAttach; 
} 
} 

return implode('', $arrRet); 
} 

public function setUseSMTPServer($boolean = null) 
{ 
// 외부 SMTP 서버를 이용할 것인지를 셋한다 
return (is_null($boolean)) ? $this->UseSMTPServer : $this->UseSMTPServer = $boolean;
 } 

public function setSMTPServer($smtpServer = null, $port = 25) 
{ 
// 외부 SMTP 서버를 이용할 경우 SMTP 서버를 설정한다 
$this->SMTPPort = $port; 
return (is_null($smtpServer)) ? $this->SMTPServer : $this->SMTPServer = $smtpServer;
 } 

public function setSMTPUser($User = null) 
{ 
// 외부 SMTP 서버를 이용할 경우 로그인 사용자를 설정한다 
return (is_null($User)) ? $this->SMTPAuthUser : $this->SMTPAuthUser = $User; 
} 

public function setSMTPPasswd($Passwd = null) 
{ 
// 외부 SMTP 서버를 이용할 경우 로그인 비밀번호를 설정한다 
return (is_null($Passwd)) ? $this->SMTPAuthPasswd : $this->SMTPAuthPasswd = $Passwd;
 } 

protected function encodingContents($contets) 
{ 
// 메일본문을 인코딩하는 역할을 한다 
return chunk_split(base64_encode($contets)); 
} 

protected function makeMailHeader() 
{ 
// 보낼 메일의 헤더를 작성한다 
$header = ""; 
foreach($this->MailHeaderArray as $Key => $Val) 
$header .= $Key . ": " . $Val . "\r\n"; 

return $header . "\r\n"; 
} 

public function send() 
{ 
// 메일을 전송한다 
$this->AddBasicHeader(); // 메일의 기본헤더를 생성한다 

if($this->UseSMTPServer) return $this->_SMTPSend(); // 외부 SMTP 서버를 이용할 경우 
else return $this->_localSend(); // 로컬 SMTP 를 이용할 경우 
} 

protected function _SMTPSend() 
{ 
/* 
* 외부 SMTP 서버를 이용할 경우 소켓접속을 통해서 메일을 전송한다 
*/ 
$Succ = 0; 

if($this->SMTPServer) { 
$this->addHeader('Subject', $this->Subject); // 메일헤더에 제목을 추가한다 
$MailBody = $this->makeMailBody(); // 메일본문을 생성한다 

if(count($this->MailTo) > 0) { // 받는 메일이 있으면 다음 작업을 반복한다 
foreach($this->MailTo as $Email => $Name) { 
$mailTo = ($Name) ? $Name . ' <' . $Email . '>' : $Email; // 받는사람 
$this->addHeader('To', $mailTo); // 메일헤더에 받는사람을 추가한다 

$Contents = $this->makeMailHeader() . "\r\n" . $MailBody; // 메일헤더와 본문을 이용해 전송할 메일을 생성한다
 
$this->Socket = fsockopen($this->SMTPServer, $this->SMTPPort); // 소켓접속한다 
if($this->Socket) { 
$this->_sockPut('HELO ' . $this->SMTPServer); 
if($this->SMTPAuthUser) { // SMTP 인증 
$this->_sockPut('AUTH LOGIN'); 
$this->_sockPut(base64_encode($this->SMTPAuthUser)); 
$this->_sockPut(base64_encode($this->SMTPAuthPasswd)); 
} 
$this->_sockPut('MAIL From:' . $this->ReplyTo); // 보내는 메일 
$this->_sockPut('RCPT To:' . $Email); // 받는메일 
$this->_sockPut('DATA'); 
$this->_sockPut($Contents); // 메일내용 
$Result = $this->_sockPut('.'); // 전송완료 
if(strpos($Result, '250 Ok: queued') !== false) $Succ++; // 성공여부판단
 $this->_sockPut('QUIT'); // 접속종료 
} 
} 
} 
} else $Succ = $this->_localSend(); // 외부 SMTP 서버를 이용하지 않으면 로컬 SMTP를 이용해서 전송한다 

return $Succ; 
//return $Result;
} 

protected function _sockPut($str) 
{ 
// 소켓접속시 내용전송 및 결과값 받기 
@fputs($this->Socket, $str . "\r\n"); 
return @fgets($this->Socket, 512); 
} 

protected function _localSend() 
{ 
$Contents = $this->makeMailBody(); // 메일본문을 작성한다 

$Succ = 0; 
foreach($this->MailTo as $Email => $Name) { 
$toMail = ($Name) ? $Name . ' <' . $Email . '>' : $Email; // 받는메일 
$this->addHeader('To', $toMail); // 메일헤더에 받는메일을 추가한다 
$header = $this->makeMailHeader(); // 헤더를 작성한다 

if(mail($Email, $this->Subject, $Contents, $header)) $Succ++; // 성공여부 판단 
} 

return $Succ; 
} 

protected function makeMailBody() 
{ 
// 메일의 본문을 작성한다 
$mailbody = ""; 

if(count($this->Attach) > 0) { // 첨부파일이 있을 경우 본문을 인코딩하여 만든다 
$mailbody .= "--" . $this->Boundary . "\r\n"; 
$mailbody .= "Content-Type: text/html; charset=" . $this->Charset . "\r\n"; 
$mailbody .= "Content-Transfer-Encoding: base64\r\n\r\n"; 
$mailbody .= $this->encodingContents($this->MailBody) . "\r\n\r\n"; 
$mailbody .= "\r\n" . $this->MailAttach(); 
} else $mailbody = $this->MailBody; // 첨부파일이 없으면 그냥 HTML 형식으로 메일본문을 생성한다 

return $mailbody; 
} 
} 

?> 
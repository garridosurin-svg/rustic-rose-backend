<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../includes/bootstrap.php';
function prompt($label){ echo $label; return trim((string)fgets(STDIN)); }
$name=prompt('Administrator name: '); $username=prompt('Username: '); $password=prompt('Password (12+ characters): ');
if(!valid_length($name,2,120)||!preg_match('/^[A-Za-z0-9_.@-]{3,80}$/',$username)||strlen($password)<12){fwrite(STDERR,"Invalid input. Use a 3-80 character username and a password of at least 12 characters.\n");exit(1);}
$hash=password_hash($password,PASSWORD_DEFAULT);
try{$st=$pdo->prepare('INSERT INTO admins(name,username,password,is_active) VALUES(?,?,?,1)');$st->execute(array($name,$username,$hash));echo "Administrator created successfully. Delete shell history containing credentials, if applicable.\n";}catch(Throwable $e){fwrite(STDERR,"Could not create administrator. The username may already exist.\n");exit(1);}

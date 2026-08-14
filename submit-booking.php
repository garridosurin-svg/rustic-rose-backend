<?php
require __DIR__.'/cors.php';
require __DIR__.'/../includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_fail('Method not allowed.',405);
if (!empty($_POST['website'])) api_ok(['reference'=>'Received']);
$required=['first_name','last_name','email','phone','service','event_date','guest_count'];
foreach($required as $field){ if(trim($_POST[$field] ?? '')==='') api_fail('Please complete all required fields.'); }
if(empty($_POST['consent'])) api_fail('Please accept the contact consent checkbox.');
$allowedServices=['Day-of Coordination','Month-of Coordination','Partial Planning','Full Planning','Elopement Package','Charcuterie Boards','Venue Management','Not sure yet'];
if (!valid_length(trim($_POST['first_name']),2,80) || !valid_length(trim($_POST['last_name']),2,80) || !valid_length(trim($_POST['phone']),7,40) || !in_array(trim($_POST['service']),$allowedServices,true) || !valid_event_date($_POST['event_date']) || ((int)$_POST['guest_count']<1 || (int)$_POST['guest_count']>10000) || !valid_length(trim($_POST['message'] ?? ''),0,3000)) api_fail('Please check the information and try again.');
if(!filter_var($_POST['email'],FILTER_VALIDATE_EMAIL) || !valid_length($_POST['email'],5,160)) api_fail('Please enter a valid email address.');
try{
    $available=[];
    $stColumns=$pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema='public' AND table_name='bookings'");
    foreach($stColumns->fetchAll() as $column) $available[$column['column_name']]=true;
    $cols=booking_columns($pdo);
    $reference='RR-'.date('Y').'-'.strtoupper(substr(bin2hex(random_bytes(5)),0,8));
    $data=[
      $cols['reference']=>$reference,'first_name'=>trim($_POST['first_name']),'last_name'=>trim($_POST['last_name']),
      'email'=>trim($_POST['email']),'phone'=>trim($_POST['phone']),'service'=>trim($_POST['service']),
      'event_date'=>$_POST['event_date'],'guest_count'=>(int)$_POST['guest_count'],$cols['message']=>trim($_POST['message'] ?? ''),
      'status'=>'Pending','estimated_revenue'=>0,'ip_address'=>$_SERVER['REMOTE_ADDR'] ?? ''
    ];
    if(isset($available[$cols['consult_date']])) $data[$cols['consult_date']]=$_POST['event_date'];
    if(isset($available[$cols['consult_time']])) $data[$cols['consult_time']]=null;
    $filtered=[]; foreach($data as $column=>$value) if(isset($available[$column])) $filtered[$column]=$value;
    $names=array_keys($filtered);
    $sql='INSERT INTO bookings ('.implode(',',array_map('qi',$names)).') VALUES ('.implode(',',array_fill(0,count($names),'?')).')';
    $pdo->prepare($sql)->execute(array_values($filtered));
    api_ok(['reference'=>$reference]);
}catch(Throwable $e){ error_log(date('c').' API submit: '.$e->getMessage().PHP_EOL,3,__DIR__.'/../logs/error.log'); api_fail('The request could not be saved. Please try again.',500); }

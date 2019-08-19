<?php
include "UHPPOTE.php";

// check for valid command line
$cardip = $argv[1];
$cardsn = $argv[2];
$command = $argv[3];
if ($cardip == "help" || !$argv[1] || !$argv[2] || !$argv[3]) {
  $command = "help";
}

// configure parameters
$data = null;
$dt=null;
switch($command) {
  // no parameters required:
  case "get_time":
  case "dev_status":
  case "get_auth_rec":
  case "get_record_index":
  case "get_ripp":
  case "del_auth_all":
  case "search":
    break;
  case "open_door":
  case "door_delay_get":
    echo "Door: " . $argv[4];
    $data = [ 'door' => $argv[4] ];
    break;
  case "set_time":
    $dt = New DateTime('now', new DateTimeZone('America/Chicago'));
    break;
  case "get_auth":
    $data = [ 'cardid' => $argv[4] ];
    break;
  case "set_ripp":
    $data = [ 'ip' => $argv[4] ];
    break;
  case "door_delay":
    $data = [ 'seconds' => $argv[4] ];
    break;
  case "del_auth":
    $data = [ 'cardid' => $argv[4] ];
    break;
  case "add_auth":
    $data = [ 'cardid' => $argv[4],
              'beg' => $argv[5],
              'end' => $argv[6],
              'ta1' => '01',
              'ta2' => '01',
              'ta3' => '01',
              'ta4' => '01',
            ];
    break;
  case "help":
    showHelp();
    exit(0);
    break;
  default:
    echo "\n";
    echo "Command not recognised: " . $argv[3] . "\n";
    showHelp();
    exit(1);
    break;
}
  
$a = new uhppote(); 

$a->setSn($cardsn);
$ip = $cardip;

#$a->setSn("19395b5e");
#$a->setSn("19395d30");

print_r($data);

$cmd = $a->getCmdHex($argv[3],$dt,$data);

echo "Send the following command to network\n$cmd\n";

$port = 60000;

$sock = createSocket();

$input = hex2bin($cmd);

echo "Sending....\n";
if( ! socket_sendto($sock, $input , strlen($input) , 0 , $cardip , $port))
{
  $errorcode = socket_last_error();
  $errormsg = socket_strerror($errorcode);
  echo "There is an error:\n";
  echo $errorcode . " -- " . $errormsg . "\n";
  exit;
}

echo "Listening for return status\n";
$reply = getReturnPacket($sock);

echo "Processing return status\n";
$procmsg = $a->procCmd(bin2hex($reply));

print_r($procmsg);
echo "\n";

function getRecord($uhppote,$socket, $recordIndex,$cardip,$port) {
  $cmd = $uhppote->getCmdHex('get_records',$recordIndex);
  $input = hex2bin($cmd);
//  echo "Sending get record $recordIndex....\n";
  if( ! socket_sendto($socket, $input , strlen($input) , 0 , $cardip , $port))
  {
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);
    die("There is error");
  }
  $reply = getReturnPacket($socket);
//  echo "Processing return $recordIndex status\n";
  $procmsg = $uhppote->procCmd(bin2hex($reply));

//  echo "Card: " . $procmsg[CardId] . " -- Date/Time: " . $procmsg[swipeymdhms] . " -- Type: " . $procmsg[rType] . " -- Door: " . $procmsg[Door] . " -- Door Stat: " . $procmsg[DoorStat] . "\n";
  print_r($procmsg);

}




function createSocket() 
{
  if(!($sock = socket_create(AF_INET, SOCK_DGRAM, 0)))
  {
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);

    die("Couldn't create socket: [$errorcode] $errormsg \n");
   }
   return $sock;
}


function getReturnPacket($sock) 
{
  if(socket_recv ( $sock , $reply , 2045 , MSG_WAITALL ) === FALSE)
  {
     $errorcode = socket_last_error();
     $errormsg = socket_strerror($errorcode);

    die("Receive socket Error: [$errorcode] $errormsg \n");
  }
  return $reply;
}

function showHelp()
{
  echo "\n";
  echo "Usage: \n";
  echo "\n";
  echo "php -f sendCommand.php <ip address> <serial> <command> [options]\n";
  echo "\n";
  echo "\n";
  echo "Commands:\n";
  echo "\n";
  echo "get_time -- No parameters required.\n";
  echo "dev_status -- No parameters required.\n";
  echo "get_auth_rec -- No parameters required.\n";
  echo "get_record_index -- No parameters required.\n";
  echo "get_ripp -- No parameters required.\n";
  echo "del_auth_all -- No parameters required.\n";
  echo "search -- No parameters required.\n";
  echo "open_door <door number> -- Example: 03\n";
  echo "door_delay_get <door number> -- Example: 02\n";
  echo "set_time  -- No parameters required. Uses system time where this command is run.\n";
  echo "get_auth <cardid> -- Example: 10012345\n";
  echo "set_ripp <local ip address>\n";
  echo "door_delay <seconds>\n";
  echo "del_auth  <cardid> -- Example: 10012345\n";
  echo "add_auth  <cardid> <YYYYMMDD> <YYYYMMDD> -- Example: 10012345 20190101 20200101\n";
  echo "help -- Shows this information\n";
  echo "\n";
}

?>

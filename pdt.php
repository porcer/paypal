<?
$pp_hostname = "www.sandbox.paypal.com"; // Change to www.sandbox.paypal.com to test against sandbox
$auth_token = "some_token";
$return="pdt_address.com/address.php"; //for PDT
$ipn="ipn_adress.com/address.php"; //for IPN
$business="some_mail@gmail.com"; //receiver email
//data for connfirmation email
$from_name="test";
$retemail="test@test.com";
$subject="Your Coupon Code";




include("db.php"); //db connector
?>
<!doctype html>
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta id="p7DMM" name="viewport" content="width=device-width">
<meta charset="utf-8">
<title>test</title>

<script type="text/javascript"><!--


function validateEmail($email) {
  var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
  if( !emailReg.test( $email ) ) {
    return false;
  } else {
    return true;
  }
}
function Form1_Validator(theForm)
{
  if (theForm.email.value == "")
  {
    alert("Please enter  e-mail address.");
    theForm.email.focus();
    return (false);
  }
  else {
	 if(!validateEmail(theForm.email.value)) {
		 alert("Please enter a valid e-mail address.");
    	theForm.email.focus();
    	return (false);
	 }
  }

  if (theForm.name.value == "")
  {
    alert("Please enter company name.");
    theForm.name.focus();
    return (false);
  }
  $('#custom').val(theForm.name.value+":"+theForm.email.value);
  
}
//--></script>
</head>

<body>



      <div>
      <? if($_REQUEST['st']=="Completed"){
		   		$error=false;
				$errison="";
				$req = 'cmd=_notify-synch';
 				$tx_token = $_GET['tx'];
				$req .= "&tx=$tx_token&at=$auth_token";
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "https://$pp_hostname/cgi-bin/webscr");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Host: $pp_hostname"));
				$res = curl_exec($ch);
				curl_close($ch);

				if(!$res){
					$log = fopen("pdt.log", "a");
					fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
					fwrite($log,"ERROR - HTTP\r\nPayPal");					 
					fclose($log);
					$error=true;
   				 }
				 else{
					$lines = explode("\n", $res);
    				$keyarray = array();
     				if (strcmp ($lines[0], "SUCCESS") == 0) {
        				for ($i=1; $i<count($lines);$i++){
        					list($key,$val) = explode("=", $lines[$i]);
        					$keyarray[urldecode($key)] = urldecode($val);
						}				
    					// check the payment_status is Completed
 						if($keyarray['payment_status']!="Completed"){
							$log = fopen("pdt.log", "a");
							fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
							fwrite($log, "TXN ID " .$keyarray['txn_id']. "\n");
							fwrite($log,"ERROR - payment_status=".$keyarray['payment_status']."\r\nPayPal");
							fclose($log);
							$error=true;
						}
						// check that txn_id has not been previously processed
						if(!$keyarray['txn_id']){
							$log = fopen("pdt.log", "a");
							fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
							fwrite($log,"ERROR - No txn_id\r\nPayPal");
							fclose($log);
							$error=true;
						}
						else {
							$txn_id=$keyarray['txn_id'];
							$query = "Select * from tblOrderHistory Where TXN_ID = '$txn_id'";
							$result = mysql_query($query) or die(mysql_error());
							$row = mysql_num_rows($result);
							if($row==0){
								// check that receiver_email is your Primary PayPal email
								if($keyarray['receiver_email']!=$business || $keyarray["txn_type"] != "web_accept"){
									$log = fopen("pdt.log", "a");
									fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
									fwrite($log, "TXN ID " .$keyarray['txn_id']. "\n");
									fwrite($log,"ERROR - receiver_email=".$keyarray['receiver_email']."\r\nPayPal");
									fclose($log);
									$error=true;
								}
    							// check that payment_amount/payment_currency are correct
								if($keyarray['mc_currency']!="USD"){
									$log = fopen("pdt.log", "a");
									fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
									fwrite($log, "TXN ID " .$keyarray['txn_id']. "\n");
									fwrite($log,"ERROR - mc_currency=".$keyarray['mc_currency']."\r\nPayPal");
									fclose($log);
									$error=true;
									}
								if($keyarray['option_selection1']=="Commercial (4 Members & 1 Business Card Ad in Newsletter and website, with link to corporate site)" || $keyarray['option_selection1']=="Institutional Level A (50 Members)" || $keyarray['option_selection1']=="Institutional Level B (10 Members)" || $keyarray['option_selection1']=="Institutional Level C (4 Members)" || $keyarray['option_selection1']=="Individual"){
    								if($keyarray['option_selection1']=="Individual" && $keyarray['mc_gross']!=15.00){
										$log = fopen("pdt.log", "a");
										fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
										fwrite($log, "TXN ID " .$keyarray['txn_id']. "\n");
										fwrite($log,"ERROR - Individual - ".$keyarray['mc_gross']."\r\nPayPal");
										fclose($log);
										$error=true;
									}
									if($keyarray['option_selection1']=="Institutional Level C (4 Members)" && $keyarray['mc_gross']!=50.00){
										$log = fopen("pdt.log", "a");
										fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
										fwrite($log, "TXN ID " .$keyarray['txn_id']. "\n");
										fwrite($log,"ERROR - Institutional Level C (4 Members) - ".$keyarray['mc_gross']."\r\nPayPal");
										fclose($log);
										$error=true;
									}
									if($keyarray['option_selection1']=="Institutional Level B (10 Members)" && $keyarray['mc_gross']!=125.00){
										$log = fopen("pdt.log", "a");
										fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
										fwrite($log, "TXN ID " .$keyarray['txn_id']. "\n");
										fwrite($log,"ERROR - Institutional Level B (10 Members) - ".$keyarray['mc_gross']."\r\nPayPal");
										fclose($log);
										$error=true;
									}
									if($keyarray['option_selection1']=="Institutional Level A (50 Members)" && $keyarray['mc_gross']!=500.00){
										$log = fopen("pdt.log", "a");
										fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
										fwrite($log, "TXN ID " .$keyarray['txn_id']. "\n");
										fwrite($log,"ERROR - Institutional Level A (50 Members) - ".$keyarray['mc_gross']."\r\nPayPal");
										fclose($log);
										$error=true;
									}
									if($keyarray['option_selection1']=="Commercial (4 Members & 1 Business Card Ad in Newsletter and website, with link to corporate site)" && $keyarray['mc_gross']!=90.00){
										$log = fopen("pdt.log", "a");
										fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
										fwrite($log, "TXN ID " .$keyarray['txn_id']. "\n");
										fwrite($log,"ERROR - Commercial (4 Members & 1 Business Card Ad in Newsletter and website, with link to corporate site) - ".$keyarray['mc_gross']."\r\nPayPal");
										fclose($log);
										$error=true;
									}
									if($keyarray['option_selection1']=="Individual") $Quantity=1;
									if($keyarray['option_selection1']=="Institutional Level C (4 Members)") $Quantity=4;
									if($keyarray['option_selection1']=="Institutional Level B (10 Members)") $Quantity=10;
									if($keyarray['option_selection1']=="Institutional Level A (50 Members)") $Quantity=50;
									if($keyarray['option_selection1']=="Commercial (4 Members & 1 Business Card Ad in Newsletter and website, with link to corporate site)" ) $Quantity=4;
					 			}
								else {
									$log = fopen("pdt.log", "a");
									fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
									fwrite($log, "TXN ID " .$keyarray['txn_id']. "\n");
									fwrite($log,"ERROR - option_selection1 = ".$keyarray['option_selection1']."\r\nPayPal");
									fclose($log);
									$error=true;
								}
				if($keyarray['custom'])$pieces = explode(":",$keyarray['custom']);
				else {
					$log = fopen("pdt.log", "a");
					fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
					fwrite($log, "TXN ID " .$keyarray['txn_id']. "\n");
					fwrite($log,"ERROR - missing custom\r\nPayPal");
					fclose($log);
					$error=true;
				}
				$company=$pieces[0];
				$email=$pieces[1];
				if($company=="" || !$company || !$email || $email==""){
					$log = fopen("pdt.log", "a");
					fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
					fwrite($log, "TXN ID " .$keyarray['txn_id']. "\n");
					fwrite($log,"ERROR - missing email or company name\r\nPayPal");
					fclose($log);
					$error=true;
				}
					$itemname = $keyarray['option_selection1'];
    				$payer_id = $keyarray['payer_id'];
					$payment_amount = $keyarray['mc_gross'];
					$txn_id = $keyarray['txn_id'];
					$payer_email = $keyarray['payer_email'];
					$fee = $keyarray['mc_fee'];
					$FirstName = $keyarray['first_name']; 
					$LastName = $keyarray['last_name'];
					$Address = $keyarray['address_street'];
					$DatePosted = date('Y-m-d');
					$end=(date('Y')+1).date('-m-d');
					$City = $keyarray['address_city'];
					$State = $keyarray['address_state'];
					$ZipCode = $keyarray['address_zip'];
					$Country = $keyarray['address_country'];
					$Tax = $keyarray['tax'];
					$Shipping = $keyarray['shipping'];
					$ContactName = $FirstName." ".$LastName;
					$CouponCode = md5($txn_id);
					
					if(!$error){
						$error=false;
						$query = "Insert Into tblOrderHistory(
									ExpirationDate,
									CompanyName,
									Companyemail,
									Item,
									Fee,
									Payerid,
									ContactName,
									Email,
									Address1,
									City,
									State,
									ZipCode,
									Country,
									TXN_ID,
									Shipping,
									Tax,
									Total,
									MembershipDate,
									CouponCode,
									NumberUsers) 
								Values (
									'$end',
									'$company',
									'$email',
									'$itemname',
									'$fee',
									'$payer_id',
									'$ContactName',
									'$payer_email',
									'$Address',
									'$City',
									'$State',
									'$ZipCode',
									'$Country',
									'$txn_id',
									'$Shipping',
									'$Tax',
									'$payment_amount',
									'$DatePosted',
									'$CouponCode',
									'$Quantity')";
					
						mysql_query($query) or die(mysql_error());
							echo ("<p><h1>Thank you for your payment.</h1></p>");
							echo ("<p><h1>Your Coupon Code: $CouponCode </h1></p>");
       						echo ("<b>Payment Details</b><br>\n");
    						echo ("<li>Name: $ContactName</li>\n");
    						echo ("<li>Item: $itemname</li>\n");
    						echo ("<li>Amount: $payment_amount</li>\n");
							echo ("<li>Company Name: $company</li>\n");
							echo ("<li>Email: $email</li>\n");
    						echo ("");
							$headers = 
								'Return-Path: ' . $retemail . "\r\n" . 
								'From: ' . $from_name . ' <' . $retemail . '>' . "\r\n" . 
								'X-Priority: 3' . "\r\n" . 
								'X-Mailer: PHP ' . phpversion() .  "\r\n" . 
								'Reply-To: ' . $from_name . ' <' . $retemail . '>' . "\r\n" .
								'MIME-Version: 1.0' . "\r\n" . 
								'Content-Transfer-Encoding: 8bit' . "\r\n" . 
								'Content-Type: text/plain; charset=UTF-8' . "\r\n";
							$message=	"Thank you for your payment.\nYour Coupon Code: $CouponCode\nPayment Details\nName: $ContactName\nItem: $itemname\nAmount: $ $payment_amount\nCompany Name: $company\nEmail: $email\n" ;
							$query = "SELECT Ismailsent FROM tblOrderHistory  WHERE CouponCode = '$CouponCode'";
							$sm=mysql_query($query) or die(mysql_error());
							$issent=mysql_fetch_assoc($sm);
							if($issent !=1){ 
								if(mail($email,$subject,$message,$headers)){
									$query = "UPDATE tblOrderHistory SET Ismailsent=1 WHERE CouponCode = '$CouponCode'";
									mysql_query($query) or die(mysql_error());
								}
							}
					}
			}
			elseif($row==1){
				$dbres = mysql_fetch_assoc($result);
				$ContactName = $dbres['ContactName'];
				$CouponCode = $dbres['CouponCode'];
				$payment_amount = $dbres['Total'];
				$company = $dbres['CompanyName'];
				$itemname = $dbres['Companyemail'];
				$email = $dbres['Item'];
				echo ("<p><h1>Thank you for your payment.</h1></p>");
				echo ("<p><h1>Your Coupon Code: $CouponCode </h1></p>");
       			echo ("<b>Payment Details</b><br>\n");
    			echo ("<li>Name: $ContactName</li>\n");
    			echo ("<li>Item: $itemname</li>\n");
    			echo ("<li>Amount: $payment_amount</li>\n");
				echo ("<li>Company Name: $company</li>\n");
				echo ("<li>Email: $email</li>\n");
    			echo ("");
				if($dbres['Ismailsent'] !=1){
						$headers = 
								'Return-Path: ' . $retemail . "\r\n" . 
								'From: ' . $from_name . ' <' . $retemail . '>' . "\r\n" . 
								'X-Priority: 3' . "\r\n" . 
								'X-Mailer: PHP ' . phpversion() .  "\r\n" . 
								'Reply-To: ' . $from_name . ' <' . $retemail . '>' . "\r\n" .
								'MIME-Version: 1.0' . "\r\n" . 
								'Content-Transfer-Encoding: 8bit' . "\r\n" . 
								'Content-Type: text/plain; charset=UTF-8' . "\r\n";
							$message=	"Thank you for your payment.\nYour Coupon Code: $CouponCode\nPayment Details\nName: $ContactName\nItem: $itemname\nAmount: $ $payment_amount\nCompany Name: $company\nEmail: $email\n" ;
						if(mail($email,$subject,$message,$headers)){
							$query = "UPDATE tblOrderHistory SET Ismailsent=1 WHERE CouponCode = '$CouponCode'";
							mysql_query($query) or die(mysql_error());
						}
					}
			}
  			else{
				echo ("<p><h1>ERROR</h1></p>");
				$log = fopen("pdt.log", "a");
				fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
				fwrite($log, "TXN ID " .$keyarray['txn_id']. "\n");
				fwrite($log,"ERROR - more than 1 txn_id\r\nPayPal");
				fclose($log);
				$error=true;
			}
		}
				}
		}
	  }
		else echo "<h1>Volunteers and Teamwork make it happen!</h1>";?>
		
			<h2>We have a variety of Memberships levels to meet your needs:</h2><br>
         
            <form name="pp_form" id="pp_form" action="https://<?=$pp_hostname?>/cgi-bin/webscr" method="post" target="_top"  onsubmit="return Form1_Validator(this)">
				<input type="hidden" name="cmd" value="_xclick">
				<input type="hidden" name="business" value="<?=$business?>">
				<input type="hidden" name="lc" value="US">
				<input type="hidden" name="item_name" value="Membership">
				<input type="hidden" name="button_subtype" value="services">
				<input type="hidden" name="no_note" value="0">
				<input type="hidden" name="currency_code" value="USD">
				<input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynowCC_LG.gif:NonHostedGuest">
				<input type="hidden" name="on0" value="Membership Level">
				<input type="hidden" name="option_select0" value="Individual">
				<input type="hidden" name="option_amount0" value="15.00">
				<input type="hidden" name="option_select1" value="Institutional Level C (4 Members)">
				<input type="hidden" name="option_amount1" value="50.00">
				<input type="hidden" name="option_select2" value="Institutional Level B (10 Members)">
				<input type="hidden" name="option_amount2" value="125.00">
				<input type="hidden" name="option_select3" value="Institutional Level A (50 Members)">
				<input type="hidden" name="option_amount3" value="500.00">
				<input type="hidden" name="option_select4" value="Commercial (4 Members & 1 Business Card Ad in Newsletter and website, with link to corporate site)">
				<input type="hidden" name="option_amount4" value="90.00">
				<input type="hidden" name="option_index" value="0">
				<input type="hidden" name="return" value="<?=$return?>">
                <input type="hidden" name="notify_url" value="<?=$ipn?>">
                <input type="hidden" name="rm" value="2">
                <input type="hidden" name="custom" id="custom">
				<div class="clear"></div>
				<div class="col1"><label for="name">*Company&nbsp;Name: </label></div>
				<div class="col2"><input name="name" type="text" id="name" required size="50"></div>
				<div class="clear"></div>
				<div class="col1"><label for="email">*Email: </label></div>
				<div class="col2"><input name="email" type="email" id="email" required size="50"></div>
				<div class="clear"></div>
				<div class="col1"><label for="level">*Membership&nbsp;Level: </label></div>
				<div class="col2">
                    <select name="os0" id="os0">
						<option value="Individual">Individual $15.00 USD</option>
						<option value="Institutional Level C (4 Members)">Institutional Level C (4 Members) $50.00 USD</option>
						<option value="Institutional Level B (10 Members)">Institutional Level B (10 Members) $125.00 USD</option>
						<option value="Institutional Level A (50 Members)">Institutional Level A (50 Members) $500.00 USD</option>
						<option value="Commercial (4 Members & 1 Business Card Ad in Newsletter and website, with link to corporate site)">Commercial (4 Members & 1 Business Card Ad in Newsletter and website, with link to corporate site) $90.00 USD</option>
					</select> 
                </div>
				<div class="clear"></div><br>
				<input type="image" src="https://<?=$pp_hostname?>/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
				<img alt="" border="0" src="https://<?=$pp_hostname?>/en_US/i/scr/pixel.gif" width="1" height="1">
			</form>
    
</div>
</body>
</html>
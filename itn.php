<?
$pp_hostname = "www.sandbox.paypal.com"; // Change to www.sandbox.paypal.com to test against sandbox
$auth_token = "some_token";
$return="pdt_address.com/address.php"; //for PDT
$ipn="ipn_adress.com/address.php"; //for IPN
$business="some_mail@gmail.com"; //receiver email
//data for connfirmation email
$from_name="test";
$retemail="test@test.com";
$subject="Your Coupon Code (VERIFIED)";




include("db.php"); //db connector

  $postdata=""; 
  foreach ($_POST as $key=>$value) $postdata.=$key."=".urlencode($value)."&"; 
  $postdata .= "cmd=_notify-validate"; 
  $curl = curl_init("https://$pp_hostname/cgi-bin/webscr"); 
  curl_setopt ($curl, CURLOPT_HEADER, 0); 
  curl_setopt ($curl, CURLOPT_POST, 1); 
  curl_setopt ($curl, CURLOPT_POSTFIELDS, $postdata); 
  curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0); 
  curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1); 
  curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 1); 
  $response = curl_exec ($curl); 
  curl_close ($curl); 
  $error=false;
  if ($response != "VERIFIED") {
	$log = fopen("ipn.log", "a");
	fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
	fwrite($log, "TXN ID " .$_POST['txn_id']. "\n");  
	fwrite($log,"ERROR - UnVERIFIIED payment\r\nPayPal response:");
	fwrite($log,$response);
	fclose($log);
	$error=true;
  }
  else{
  		// check the payment_status is Completed
 		if($_POST['payment_status']!="Completed"){
			$log = fopen("ipn.log", "a");
			fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
			fwrite($log, "TXN ID " .$_POST['txn_id']. "\n");
			fwrite($log,"ERROR - payment_status=".$_POST['payment_status']."\r\nPayPal response:");
			fwrite($log,$response);
			fclose($log);
			$error=true;
			}
		// check that txn_id has not been previously processed
		if(!$_POST['txn_id']){
			$log = fopen("ipn.log", "a");
			fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
			fwrite($log,"ERROR - No txn_id\r\nPayPal response:");
			fwrite($log,$response);
			fclose($log);
			$error=true;
		}
		else {
			$txn_id=$_POST['txn_id'];
			$query = "Select * from tblOrderHistory Where TXN_ID = '$txn_id'";
			$result = mysql_query($query);
			$row = mysql_num_rows($result);
			if($row==0){
				// check that receiver_email is your Primary PayPal email
				if($_POST['receiver_email']!=$business || $_POST["txn_type"] != "web_accept"){
					$log = fopen("ipn.log", "a");
					fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
					fwrite($log, "TXN ID " .$_POST['txn_id']. "\n");
					fwrite($log,"ERROR - receiver_email=".$_POST['receiver_email']."\r\nPayPal response:");
					fwrite($log,$response);
					fclose($log);
					$error=true;
				}
    			// check that payment_amount/payment_currency are correct
				if($_POST['mc_currency']!="USD"){
					$log = fopen("ipn.log", "a");
					fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
					fwrite($log, "TXN ID " .$_POST['txn_id']. "\n");
					fwrite($log,"ERROR - mc_currency=".$_POST['mc_currency']."\r\nPayPal response:");
					fwrite($log,$response);
					fclose($log);
					$error=true;
				}
				if($_POST['option_selection1']=="Commercial (4 Members & 1 Business Card Ad in Newsletter and website, with link to corporate site)" || $_POST['option_selection1']=="Institutional Level A (50 Members)" || $_POST['option_selection1']=="Institutional Level B (10 Members)" || $_POST['option_selection1']=="Institutional Level C (4 Members)" || $_POST['option_selection1']=="Individual"){
    					if($_POST['option_selection1']=="Individual" && $_POST['mc_gross']!=15.00){
							$log = fopen("ipn.log", "a");
							fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
							fwrite($log, "TXN ID " .$_POST['txn_id']. "\n");
							fwrite($log,"ERROR - Individual - ".$_POST['mc_gross']."\r\nPayPal response:");
							fwrite($log,$response);
							fclose($log);
							$error=true;
						}
						if($_POST['option_selection1']=="Institutional Level C (4 Members)" && $_POST['mc_gross']!=50.00){
							$log = fopen("ipn.log", "a");
							fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
							fwrite($log, "TXN ID " .$_POST['txn_id']. "\n");
							fwrite($log,"ERROR - Institutional Level C (4 Members) - ".$_POST['mc_gross']."\r\nPayPal response:");
							fwrite($log,$response);
							fclose($log);
							$error=true;
							}
						if($_POST['option_selection1']=="Institutional Level B (10 Members)" && $_POST['mc_gross']!=125.00){
							$log = fopen("ipn.log", "a");
							fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
							fwrite($log, "TXN ID " .$_POST['txn_id']. "\n");
							fwrite($log,"ERROR - Institutional Level B (10 Members) - ".$_POST['mc_gross']."\r\nPayPal response:");
							fwrite($log,$response);
							fclose($log);
							$error=true;
							}
						if($_POST['option_selection1']=="Institutional Level A (50 Members)" && $_POST['mc_gross']!=500.00){
							$log = fopen("ipn.log", "a");
							fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
							fwrite($log, "TXN ID " .$_POST['txn_id']. "\n");
							fwrite($log,"ERROR - Institutional Level A (50 Members) - ".$_POST['mc_gross']."\r\nPayPal response:");
							fwrite($log,$response);
							fclose($log);
							$error=true;
							}
						if($_POST['option_selection1']=="Commercial (4 Members & 1 Business Card Ad in Newsletter and website, with link to corporate site)" && $_POST['mc_gross']!=90.00){
							$log = fopen("ipn.log", "a");
							fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
							fwrite($log, "TXN ID " .$_POST['txn_id']. "\n");
							fwrite($log,"ERROR - Commercial (4 Members & 1 Business Card Ad in Newsletter and website, with link to corporate site) - ".$_POST['mc_gross']."\r\nPayPal response:");
							fwrite($log,$response);
							fclose($log);
							$error=true;
							}
						if($_POST['option_selection1']=="Individual") $Quantity=1;
						if($_POST['option_selection1']=="Institutional Level C (4 Members)") $Quantity=4;
						if($_POST['option_selection1']=="Institutional Level B (10 Members)") $Quantity=10;
						if($_POST['option_selection1']=="Institutional Level A (50 Members)") $Quantity=50;
						if($_POST['option_selection1']=="Commercial (4 Members & 1 Business Card Ad in Newsletter and website, with link to corporate site)" ) $Quantity=4;
					 }
				else {
					$log = fopen("ipn.log", "a");
					fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
					fwrite($log, "TXN ID " .$_POST['txn_id']. "\n");
					fwrite($log,"ERROR - option_selection1 = ".$_POST['option_selection1']."\r\nPayPal response:");
					fwrite($log,$response);
					fclose($log);
					$error=true;
					}
				if($_POST['custom'])$pieces = explode(":",$_POST['custom']);
				else {
					$log = fopen("ipn.log", "a");
					fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
					fwrite($log, "TXN ID " .$_POST['txn_id']. "\n");
					fwrite($log,"ERROR - missing custom\r\nPayPal response:");
					fwrite($log,$response);
					fclose($log);
					$error=true;
				}
				$company=$pieces[0];
				$email=$pieces[1];
				if($company=="" || !$company || !$email || $email==""){
					$log = fopen("ipn.log", "a");
					fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
					fwrite($log, "TXN ID " .$_POST['txn_id']. "\n");
					fwrite($log,"ERROR - missing email or company name\r\nPayPal response:");
					fwrite($log,$response);
					fclose($log);
					$error=true;
				}
					$itemname = $_POST['option_selection1'];
    				$payer_id = $_POST['payer_id'];
					$payment_amount = $_POST['mc_gross'];
					$txn_id = $_POST['txn_id'];
					$payer_email = $_POST['payer_email'];
					$fee = $_POST['mc_fee'];
					$FirstName = $_POST['first_name']; 
					$LastName = $_POST['last_name'];
					$Address = $_POST['address_street'];
					$DatePosted = date('Y-m-d');
					$end=(date('Y')+1).date('-m-d');
					$City = $_POST['address_city'];
					$State = $_POST['address_state'];
					$ZipCode = $_POST['address_zip'];
					$Country = $_POST['address_country'];
					$Tax = $_POST['tax'];
					$Shipping = $_POST['shipping'];
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
									NumberUsers,
									Isverified) 
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
									'$Quantity',
									1)";
					
						mysql_query($query) or die(mysql_error());
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
				$rowres = mysql_fetch_assoc($result);
				if($rowres['Isverified']!=1){
					$query = "UPDATE tblOrderHistory SET Isverified=1 WHERE TXN_ID = '$txn_id'";
					mysql_query($query) or die(mysql_error());
					if($rowres['Ismailsent'] !=1){
						$ContactName = $rowres['ContactName'];
						$CouponCode = $rowres['CouponCode'];
						$payment_amount = $rowres['Total'];
						$company = $rowres['CompanyName'];
						$email = $rowres['Companyemail'];
						$itemname = $rowres['Item'];
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
			}
  			else{
				$log = fopen("ipn.log", "a");
				fwrite($log, "\n\nipn - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n");
				fwrite($log, "TXN ID " .$_POST['txn_id']. "\n");
				fwrite($log,"ERROR - more than 1 txn_id\r\nPayPal response:");
				fwrite($log,$response);
				fclose($log);
				$error=true;
			}
		}
	}
?>
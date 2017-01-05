<?php
require_once("classes/class.global_settings.php");
if (GlobalSettings::GetSiteOnlineStatus() == 0)
{
	header('location:under_maintenance.php');
	exit;
}

ob_start();
echo 1;
require_once("classes/class.users.php");
require_once("classes/class.members.php");
require_once("classes/class.member_details.php");
require_once("classes/class.authentication.php");

require_once("classes/class.member_helpers.php");
require_once("classes/class.states.php");
require_once("classes/class.branches.php");

require_once("includes/global_defaults.inc.php");
require_once("includes/magic_quotes.inc.php");

//1. RECHECK IF THE USER IS VALID //
try
{
	$AuthObject = new ApplicationAuthentication;
	$LoggedUser = new User(0, $AuthObject->CheckValidUser());
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->GetUserType() != 2)
{
	header('location:unauthorized_login_admin.php');
	exit;
}

try
{
	$MemberObject = new MemberDetails(0, $LoggedUser->GetUserName());
}
catch (ApplicationDBException $e)
{
	header('location:error_page.php');
	exit;
}
catch (Exception $e)
{
	header('location:error_page.php');
	exit;
}

if ($MemberObject->GetIsIDSeized() || $MemberObject->GetDaysPastRenewalDate() > 120)
{
	header('location:head_office.php');
	exit;
}

$StateArray = array();
$StateArray = State::GetStates();

$BranchesArray = array();
$BranchesArray = Branch::GetAllBranches(true);
		
$LandingPageMode = '';
if (isset($_GET['Mode']))
{
	$LandingPageMode = $_GET['Mode'];
}

require_once('html_header.php');
?>
<title>WELCOME TO BEYOND RESEARCH &amp; DEVELOPMENT LTD</title>
</head>

<style type="text/css">
#DetailsTable { border-collapse: collapse; font-size:12px; }

#DetailsTable td {
	margin: 0;
	padding: 5px 5px 5px 10px;
	color:#3366CC;
	font-weight:bold;
}

td.ui-widget-header{ color:#FFFFFF !important; padding:2px 0 2px 10px; }

.LabelHeading {
	color:#666600;
	font-weight:bold;
}
</style>

<body leftmargin="0" topmargin="0">
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center"><table width="1000" border="0" cellpadding="0" cellspacing="0" class="bdr1">
<?php 
	require_once('user_header.php');
?>
      <tr>
        <td align="center"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top" width="26%">
<?php 
	require_once('user_navigation.php');
?>
            </td>
            <td align="left" valign="top" width="74%">
						<h1 style="font-size:16px; color:#3366CC; border-bottom:3px double #669900;">Direct Seller Info</h1>
            <br style="clear:both;" />
            <div class="ui-state-default ui-corner-all" style="margin:10px;">
<?php
if ($LandingPageMode == 'EI')
{
	echo '<div class="ui-state-error ui-corner-all" style="padding:10px; margin:10px auto 10px auto; width:380px; font-weight:bold;"><span class="ui-icon ui-icon-circle-check" style="float:left;"></span> Your information was changed successfully.</div>';
}

?>
				<table id="DetailsTable" width="100%" cellpadding="0" cellspacing="0">
					<tr>
						<td><span class="LabelHeading">Direct Seller ID:</span> <?php echo $MemberObject->GetMemberCode(); ?></td>
						<td><span class="LabelHeading">Date of Registration:</span> <?php echo date("d/m/Y", strtotime($MemberObject->GetJoinDate())); ?></td>
					</tr>
					
					<tr><td colspan="2">&nbsp;</td></tr>
					<tr>
						<td class="ui-widget-header" colspan="2">Personal Details</td>
					</tr>
					<tr>
						<td colspan="2"><span class="LabelHeading">Full Name:</span> <?php echo $MemberObject->GetMemberName(); ?></td>
					</tr>
					<tr>
						<td colspan="2"><span class="LabelHeading">Father's/Husband's Name:</span> <?php echo $MemberObject->GetFatherName(); ?></td>
					</tr>
					<tr>
						<td colspan="2"><span class="LabelHeading">Address:</span> <?php echo $MemberObject->GetAddress1() . '<br />' . $MemberObject->GetAddress2(); ?></td>
					</tr>
					<tr>
						<td><span class="LabelHeading">District:</span> <?php echo $MemberObject->GetDistrictName(); ?></td>
						<td><span class="LabelHeading">State:</span> <?php echo $StateArray[$MemberObject->GetStateID()]; ?></td>
					</tr>
					<tr>
						<td><span class="LabelHeading">Pin Code:</span> <?php echo $MemberObject->GetPinCode(); ?></td>
						<td><span class="LabelHeading">STD Code:</span> <?php echo $MemberObject->GetStdCode(); ?></td>
					</tr>
					<tr>
						<td><span class="LabelHeading">Telephone No.:</span> <?php echo $MemberObject->GetPhoneNo(); ?></td>
						<td><span class="LabelHeading">Mobile No.:</span> <?php echo $MemberObject->GetMobileNo(); ?></td>
					</tr>
					<tr>
						<td colspan="2"><span class="LabelHeading">Date of Birth:</span> <?php echo date("d/m/Y", strtotime($MemberObject->GetDateOfBirth())); ?></td>
					</tr>
					<tr>
						<td><span class="LabelHeading">PAN No.:</span> <?php echo $MemberObject->GetPanNo(); ?></td>
						<td><span class="LabelHeading">Is Photocopy of PAN attached:</span> <?php echo $MemberObject->GetIsPanCopyAttached() == 1 ? 'Yes' : 'No'; ?></td>
					</tr>
                    <tr>
                        <td colspan="2"><span class="LabelHeading">E-mail:</span> <?php echo $MemberObject->GetEmail(); ?></td>
                    </tr>
					<tr><td colspan="2">&nbsp;</td></tr>
					<tr>
						<td class="ui-widget-header" colspan="2">Bank Details</td>
					</tr>
                    <tr>
                        <td colspan="2"><span class="LabelHeading">Bank Name:</span> <?php echo $MemberObject->GetBankName(); ?></td>
                    </tr>
                    <tr>
                        <td><span class="LabelHeading">Bank A/c No.:</span> <?php echo $MemberObject->GetBankAccountNo(); ?></td>
                        <td><span class="LabelHeading">IFSC Code:</span> <?php echo $MemberObject->GetIFSCCode(); ?></td>
                    </tr>
                    <tr>
                        <td><span class="LabelHeading">Branch Name:</span> <?php echo $MemberObject->GetBranchName(); ?></td>
                        <td><span class="LabelHeading">District:</span> <?php echo $MemberObject->GetBankCityName(); ?></td>
                    </tr>
                    <tr>
                        <td><span class="LabelHeading">State:</span> <?php echo $MemberObject->GetBankStateID() != 0 ? $StateArray[$MemberObject->GetBankStateID()] : ''; ?></td>
                        <td><span class="LabelHeading">Cheque Receiving Branch:</span> <?php echo $BranchesArray[$MemberObject->GetChequeReceivingBranchID()]; ?></td>
                    </tr>
					<tr><td colspan="2">&nbsp;</td></tr>
					<tr>
						<td class="ui-widget-header" colspan="2">Nominee Details</td>
					</tr>
					<tr>
						<td><span class="LabelHeading">Nominee Name:</span> <?php echo $MemberObject->GetNomineeName(); ?></td>
						<td><span class="LabelHeading">Relation:</span> <?php echo $MemberObject->GetNomineeRelation(); ?></td>
					</tr>
                    <tr>
						<td colspan="2"><span class="LabelHeading">Address:</span> <?php echo $MemberObject->GetNomineeAddress1() . '<br />' . $MemberObject->GetNomineeAddress2(); ?></td>
					</tr>
                    <tr>
						<td><span class="LabelHeading">District:</span> <?php echo $MemberObject->GetNomineeDistrict(); ?></td>
						<td><span class="LabelHeading">State:</span> <?php echo (isset($StateArray[$MemberObject->GetNomineeStateID()])) ? $StateArray[$MemberObject->GetNomineeStateID()] : '-'; ?></td>
					</tr>
					<tr>
						<td><span class="LabelHeading">Pin Code:</span> <?php echo $MemberObject->GetNomineePinCode(); ?></td>
						<td><span class="LabelHeading">STD Code:</span> <?php echo $MemberObject->GetNomineeStdCode(); ?></td>
					</tr>
					<tr>
						<td><span class="LabelHeading">Telephone No.:</span> <?php echo $MemberObject->GetNomineePhoneNo(); ?></td>
						<td><span class="LabelHeading">Mobile No.:</span> <?php echo $MemberObject->GetNomineeMobileNo(); ?></td>
					</tr>
                    <tr>
                        <td><span class="LabelHeading">Date of Birth:</span> <?php echo $MemberObject->GetNomineeDOB() != '0000-00-00' ? date("d/m/Y", strtotime($MemberObject->GetNomineeDOB())) : ''; ?></td>
                        <td><span class="LabelHeading">E-mail:</span> <?php echo $MemberObject->GetNomineeEmail(); ?></td>
                    </tr>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td class="ui-widget-header" colspan="2">Sponser Direct Seller Details</td>
					</tr>
					<tr>
						<td><span class="LabelHeading">Sponser Direct Seller ID:</span> <?php echo $MemberObject->GetFatherCode(); ?></td>
						<td><span class="LabelHeading">Sponser Direct Seller Name:</span> <?php echo ((MemberHelpers::IsMemberSeized(0, $MemberObject->GetFatherCode())) ? sprintf(LANG_DS_ID_SIEZED, '') : $MemberObject->GetSponserName()); ?></td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
				</table>		
			</td>
          </tr>
        </table></div></td>
      </tr>
      <tr>
        <td align="center">&nbsp;</td>
      </tr>
      <tr>
        <td align="center">
		
		</td>
      </tr>
<?php 
	require_once('site_footer.php');
?>
    </table></td>
  </tr>
</table>
</body>
</html>
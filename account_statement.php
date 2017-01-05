<?php
require_once("classes/class.global_settings.php");
if (GlobalSettings::GetSiteOnlineStatus() == 0)
{
	header('location:under_maintenance.php');
	exit;
}

ob_start();

require_once("classes/class.users.php");
require_once("classes/class.authentication.php");

require_once("classes/class.members.php");
require_once("classes/class.member_details.php");
require_once("classes/class.helpers.php");
require_once("classes/class.monthly_business_close.php");
require_once("classes/class.member_helpers.php");

require_once("classes/class.states.php");
require_once("classes/class.validation.php");

require_once("includes/global_defaults.inc.php");

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

if ($MemberObject->GetIsIDSeized() || $MemberObject->HasRenewalExpired())
{
	header('location:head_office.php');
	exit;
}

$CurrentFinancialYearID = 0;
$CurrentFinancialYearID = Helpers::GetCurrentFinancialYear();

$ClosedBusinessMonthsArray = array();
$ClosedBusinessMonthsArray = MonthlyBusinessClose::GetAllClosedBusinessMonths($CurrentFinancialYearID);

$StateArray = array();
$StateArray = State::GetStates();

$HasErrors = false;
$Clean = array();
$Clean['Process'] = 0;

end($ClosedBusinessMonthsArray);
$Clean['BusinessCloseID'] = key($ClosedBusinessMonthsArray);

$Clean['AgencyID'] = $MemberObject->GetMemberCode();
$Clean['MemberID'] = $MemberObject->GetMemberID();

if (isset($_POST['Process']))
{
	$Clean['Process'] = $_POST['Process'];
}
switch ($Clean['Process'])
{
	case 7:
		$NewRecordValidator = new Validator();
		
		if (isset($_POST['drdBusinessMonth']))
		{
			$Clean['BusinessCloseID'] = (int) $_POST['drdBusinessMonth'];
		}
		
		$NewRecordValidator->ValidateInSelect($Clean['BusinessCloseID'], $ClosedBusinessMonthsArray, 'Unknown Error, Please try again');	
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}		

		$MonthlyDueDetails = array();
		$MonthlyDueDetails = MemberHelpers::GetMonthlyDueDetails($Clean['MemberID'], $Clean['BusinessCloseID']);				
		
		$MemberBVDetails = array();
		$MemberBVDetails = Helpers::GetClosedMonthBVDetails($Clean['MemberID'], $Clean['BusinessCloseID']);
		
		if (!array($MemberBVDetails) || count($MemberBVDetails) <= 0)
		{
			$MemberBVDetails['LeftBusiness'] = 0;
			$MemberBVDetails['RightBusiness'] = 0;
			$MemberBVDetails['SelfBusiness'] = 0;
			$MemberBVDetails['TotalBusiness'] = 0;
		}
								
		$MemberDetails = new MemberDetails($Clean['MemberID']);
	break;
}

require_once('html_header.php');
?>
<title>WELCOME TO BEYOND RESEARCH &amp; DEVELOPMENT LTD</title>
<script>
function ShowPrintPopup()
{
	window.open ("print_custom.php","mywindow1","location=0,status=0,scrollbars=1,width=700,height=500");
	return false;
}
</script>
</head>

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
            <td valign="top" width="20%">
<?php 
	require_once('user_navigation.php');
?>
            </td>
            <td valign="top" width="80%">
						<h1 style="font-size:16px; color:#3366CC; border-bottom:3px double #669900;">Account Statement</h1>
            <div class="ui-widget" style="margin:10px 0 10px 0; font-family:Arial, Helvetica, sans-serif; font-size:12px; margin:10px;">
            	<div class="ui-widget-header ui-corner-tl ui-corner-tr" style="padding:2px 0 2px 10px;">Select a Month to veiw Statement</div>
              <div class="ui-widget-content ui-corner-bl ui-corner-br" style="padding:15px; text-align:center; font-weight:bold;">
              	<form method="post" action="account_statement.php">
                	<input type="hidden" name="Process" value="7" />
                	<select class="bdr ui-state-default" name="drdBusinessMonth" id="BusinessMonth">
<?php
						if (is_array($ClosedBusinessMonthsArray) && count($ClosedBusinessMonthsArray) > 0)
						{
							foreach($ClosedBusinessMonthsArray as $BusinessCloseID=>$BusinessMonthStartDate)
							{
								if ($BusinessCloseID == $Clean['BusinessCloseID'])
								{	
									echo '<option value="'.$BusinessCloseID.'" selected="selected">'.date("F, Y", strtotime($BusinessMonthStartDate)).'</option>';
								}
								else
								{
									echo '<option value="'.$BusinessCloseID.'">'.date("F, Y", strtotime($BusinessMonthStartDate)).'</option>';
								}
							}
						}
?>
					</select>
                  <input type="submit" value="Submit" class="ui-state-default ui-corner-all">
                </form>
							</div>
            </div>
<?php
		if ($HasErrors == true)
		{
			echo $NewRecordValidator->DisplayErrors();
		}
		if ($Clean['Process'] == 7 && is_array($MonthlyDueDetails) && count($MonthlyDueDetails) > 0)
		{
			$Total = 0;
			$NetComission = 0;
			
			$NetAmount = 0;
			
			/************** CALCULATE BV AMOUNT ******************************/
			/*******************************************************************************/			
			$TotalBusinessPercentage = 0;
			$LeftBusinessPercentage = 0;
			$RightBusinessPercentage = 0;
			
			$AmountForTotalBusiness = 0;
			$AmountForLeftBusiness = 0;
			$AmountForRightBusiness = 0;
			
			$FinalBVAmountToReceive = 0;
			
			if (is_array($MemberBVDetails) && count($MemberBVDetails) > 0 && $MemberBVDetails['TotalBusiness'] > 0)
			{
				$TotalBusinessPercentage = Helpers::GetPercentFromBV($MemberBVDetails['TotalBusiness']);			
				$AmountForTotalBusiness = round((($TotalBusinessPercentage / 100) * $MemberBVDetails['TotalBusiness']), 2);
				
				if ($MemberBVDetails['LeftBusiness'] > 0)
				{
					$LeftBusinessPercentage = Helpers::GetPercentFromBV($MemberBVDetails['LeftBusiness']);
					$AmountForLeftBusiness = round((($LeftBusinessPercentage / 100) * $MemberBVDetails['LeftBusiness']), 2);
				}	
				
				if ($MemberBVDetails['RightBusiness'] > 0)
				{
					$RightBusinessPercentage = Helpers::GetPercentFromBV($MemberBVDetails['RightBusiness']);
					$AmountForRightBusiness = round((($RightBusinessPercentage / 100) * $MemberBVDetails['RightBusiness']), 2);
				}			
			}
			
			$FinalBVAmountToReceive = $AmountForTotalBusiness - ($AmountForLeftBusiness + $AmountForRightBusiness);				
			$Total += $FinalBVAmountToReceive;
			/*******************************************************************************/
			
			$TotalLevelIncome = 0;
			$TotalBinaryIncome = 0;
			
			$TotalLevelIncome = ($MonthlyDueDetails['LevelMembers'] * 20);
			$Total += $TotalLevelIncome;
			
			$TotalBinaryIncome = ($MonthlyDueDetails['BinaryMembers'] * 150);
			$Total += $TotalBinaryIncome;
			
			$Total += $MonthlyDueDetails['OfferAmount'];
			$Total += $MonthlyDueDetails['RewardsAmount'];
			$Total += $MonthlyDueDetails['LeadershipBonusAmount'];
			$Total += $MonthlyDueDetails['LeadersClubAmount'];			
			$Total += $MonthlyDueDetails['PreviousBalance'];			
			
			$Total -= $MonthlyDueDetails['AdminCharge'];
			$NetComission = $Total - $MonthlyDueDetails['TDS'];
			
			$NetAmount = $NetComission - $MonthlyDueDetails['TDSAdjustment'] + $MonthlyDueDetails['TDSReturn'];
?>

            <div class="ui-widget" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; margin:10px;">                        
                <div class="ui-widget-content ui-corner-bl ui-corner-br" id="contDiv" style=" padding:10px;">
                    <div style="text-align:center; background:none; color:#000000; font-size:16px; font-weight:bold;">Account Statement for the month of <?php echo date("F, Y", strtotime($ClosedBusinessMonthsArray[$Clean['BusinessCloseID']])); ?></div>
                        <table width="707" cellspacing="0" class="statement" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; border-collapse:collapse;">
                          <tr>
                            <td colspan="4" style="border-bottom:1px solid #666666"><img src="http://brdltd.com/images/new1_logo.jpg" width="502" height="56" /></td>
                            <td colspan="3" style="text-align:right; vertical-align:top; border-bottom:1px solid #666666;">120/611-A, Lajpat Nagar,<br />Kanpur-208005<br />Phone : 0512-2220325<br />Email : info@brdltd.com<br />  	   http://www.brdltd.com</td>
                          </tr>
                          <tr>
                            <td width="273" rowspan="6" align="left" style="vertical-align:top;">
                                <p><strong>Agency ID. : </strong><strong style="text-decoration:underline; font-style:italic;"><?php echo str_pad($Clean['AgencyID'], 7, '0', STR_PAD_LEFT); ?></strong></p>
                                <p><strong>Agency Name : </strong><strong style="text-decoration:underline; font-style:italic;"><?php echo ucwords($MemberDetails->GetMemberName()); ?></strong>
                                </br><?php echo $MemberDetails->GetAddress1(); ?>
<?php
                                if ($MemberDetails->GetAddress2() != '')
                                {
                                    echo '</br>'.$MemberDetails->GetAddress2();
                                }
?>		
                                </br><?php echo $MemberDetails->GetDistrictName(); ?>
                                </br><?php echo ($MemberDetails->GetStateID() == 0) ? '' : $StateArray[$MemberDetails->GetStateID()]; ?></p>
                            </td>
                            <td width="71" align="center">&nbsp;</td>
                            <td width="153" align="center"><strong>Level</strong></td>
                            <td colspan="2" align="center"> <strong>Rate</strong>    </td>
                            <td width="80" >&nbsp;</td>
                            <td width="71" align="right"> <strong>Commission</strong> </td>
                          </tr>
                            <tr>
                            <td align="center"> </td>
                            <td align="center"><?php echo $MonthlyDueDetails['LevelMembers']; ?> Agencies </td>
                            <td colspan="2" align="center"> 20</td>
                            <td >&nbsp;</td>
                            <td align="right"><?php echo number_format($TotalLevelIncome, 2); ?> </td>
                          </tr>
                        <tr>
                            <td width="71" height="22" align="center">&nbsp;</td>
                            <td width="153" align="center"><strong>Binary</strong></td>
                            <td colspan="2" align="center">   </td>
                            <td >&nbsp;</td>
                           <td width="71" align="right">&nbsp;</td>
                          </tr>  <tr>
                            <td align="center"> </td>
                            <td align="center"><?php echo $MonthlyDueDetails['BinaryMembers']; ?> Pairs </td>
                            <td colspan="2" align="center"> 150</td>
                            <td >&nbsp;</td>
                            <td align="right"><?php echo number_format($TotalBinaryIncome, 2); ?></td>
                          </tr>
                          <tr>
                            <td width="71" align="center">&nbsp;</td>
                            <td width="153" align="center"><strong>Business Volume </strong></td>
                            <td colspan="2" align="center">   </td>
                            <td >&nbsp;</td>
                            <td width="71" align="right">&nbsp;</td>
                          </tr>
                          <tr>
                            <td align="center">&nbsp;</td>
                            <td align="center"><?php echo number_format($MemberBVDetails['TotalBusiness'], 0); ?> </td>
                            <td colspan="2" align="center"> <?php echo number_format($TotalBusinessPercentage, 2); ?>%</td>
                            <td align="right"><?php echo number_format($AmountForTotalBusiness, 2); ?></td>
                            <td >&nbsp;</td>
                          </tr>
                          
                          <tr>
                            <td colspan="6"><strong>Less Down   Line Commission</strong></td>
                          </tr>
                        <?php
                            if ($MonthlyDueDetails['LeftMemberID'] != 0)
                            {
                                $LeftMember = new Member($MonthlyDueDetails['LeftMemberID']);
                        ?>  
                          <tr>
                            <td><?php echo ucwords($LeftMember->GetMemberName()); ?></td>
                            <td align="center"><?php echo str_pad($LeftMember->GetMemberCode(), 7, '0', STR_PAD_LEFT); ?> </td>
                            <td align="center"><?php echo number_format($MemberBVDetails['LeftBusiness'], 0); ?></td>
                            <td colspan="2" align="center"><?php echo number_format($LeftBusinessPercentage, 2); ?>% </td>
                            <td align="right">- <?php echo number_format($AmountForLeftBusiness, 2); ?></td>
                            <td >&nbsp;</td>
                          </tr>
                        <?php
                            }	
                            if ($MonthlyDueDetails['RightMemberID'] != 0)
                            {
                                $RightMember = new Member($MonthlyDueDetails['RightMemberID']);
                        ?>  
                          <tr>
                            <td><?php echo ucwords($RightMember->GetMemberName()); ?></td>
                            <td align="center"><?php echo str_pad($RightMember->GetMemberCode(), 7, '0', STR_PAD_LEFT); ?> </td>
                            <td align="center"><?php echo number_format($MemberBVDetails['RightBusiness'], 0); ?></td>
                            <td colspan="2" align="center"><?php echo number_format($RightBusinessPercentage, 2); ?>%</td>
                            <td align="right">- <?php echo number_format($AmountForRightBusiness, 2); ?></td>
                            <td >&nbsp;</td>
                          </tr>
                        <?php
                            }
                        ?>    
                          <tr>
                            <td colspan="7" align="right"><?php echo number_format($FinalBVAmountToReceive, 2); ?></td>
                            </tr> 
                        <?php
                        if ($MonthlyDueDetails['OfferAmount'] != 0)
                        {
                        ?>  
                          <tr>
                            <td colspan="6" align="right"><strong><strong>Offer Amount :</strong></strong></td>
                            <td align="right"><?php echo number_format($MonthlyDueDetails['OfferAmount'], 2); ?></td>
                          </tr>
                        <?php
                        }
                        if ($MonthlyDueDetails['RewardsAmount'] != 0)
                        {
                        ?>  
                          <tr>
                            <td colspan="6" align="right"><strong><strong>Rewards Amount :</strong></strong></td>
                            <td align="right"><?php echo number_format($MonthlyDueDetails['RewardsAmount'], 2); ?></td>
                          </tr>
                        <?php
                        }
                        if ($MonthlyDueDetails['LeadershipBonusAmount'] != 0)
                        {
                        ?>   
                          <tr>
                            <td colspan="6" align="right"><strong><strong>Leadership Bonus Amount :</strong></strong></td>
                            <td align="right"><?php echo number_format($MonthlyDueDetails['LeadershipBonusAmount'], 2); ?></td>
                          </tr>
                        <?php
                        }
                        if ($MonthlyDueDetails['LeadersClubAmount'] != 0)
                        {
                        ?>   
                          <tr>
                            <td colspan="6" align="right"><strong><strong>Leaders Club Amount :</strong></strong></td>
                            <td align="right"><?php echo number_format($MonthlyDueDetails['LeadersClubAmount'], 2); ?></td>
                          </tr>  
                        <?php
                        }
                        if ($MonthlyDueDetails['PreviousBalance'] != 0)
                        {
                        ?>   
                          <tr>
                            <td colspan="6" align="right"><strong><strong>Previous Balance Amount :</strong></strong></td>
                            <td align="right"><?php echo number_format($MonthlyDueDetails['PreviousBalance'], 2); ?></td>
                          </tr>
                        <?php
                        }
                        ?>          
                          <tr>
                            <td colspan="6" align="right"><strong>Less Administrative Charge :</strong></td>
                            <td align="right">- <?php echo number_format($MonthlyDueDetails['AdminCharge'], 2); ?></td>
                          </tr>  
                          <tr>
                            <td colspan="6" align="right"><strong>Total Amount :</strong></td>
                            <td align="right" style="border-top:dotted 1px #000000;"><?php echo number_format($Total, 2); ?></td>
                          </tr>
                          <tr>
                            <td colspan="6" align="right"><strong>Less Deduction Amount :</strong></td>
                            <td align="right">- <?php echo number_format($MonthlyDueDetails['TDS'], 2); ?></td>
                          </tr>
                            <tr>
                            <td colspan="6" align="right"><strong><strong>Net Commission :</strong></strong></td>
                            <td align="right" style="border-top:dotted 1px #000000; border-bottom:dotted 1px #000000;"><?php echo number_format($NetComission, 2); ?></td>
                          </tr>  
                          <?php
						if ($MonthlyDueDetails['TDSAdjustment'] != 0)
                        {						
						?>
                          <tr>
                            <td colspan="6" align="right"><strong><strong>Amount Debit :</strong></strong></td>
                            <td align="right">- <?php echo number_format($MonthlyDueDetails['TDSAdjustment'], 2); ?></td>
                          </tr>
                        <?php
						}
                        if ($MonthlyDueDetails['TDSReturn'] != 0)
                        {
                        ?>   
                          <tr>
                            <td colspan="6" align="right"><strong><strong>Amount Credit :</strong></strong></td>
                            <td align="right"><?php echo number_format($MonthlyDueDetails['TDSReturn'], 2); ?></td>
                          </tr>
                          <tr>
                            <td colspan="6" align="right"><strong><strong>Net Amount :</strong></strong></td>
                            <td align="right" style="border-top:dotted 1px #000000;"><?php echo number_format($NetAmount, 2); ?></td>
                          </tr> 
                        <?php
                        }
                        ?>
                          <tr>
                            <td colspan="6" align="right"><strong><strong>Net Payable (Adjusted):</strong></strong></td>
                            <td align="right" style="border-top:dotted 1px #000000;"><strong><?php echo number_format(floor($NetAmount), 2); ?></strong></td>
                          </tr>
                        </table>
                        </div>
                      </div>

                  </div>
                </div>
    						<div class="ui-state-default ui-corner-all" style="margin:10px 20px 10px 0; padding:10px; width:50px; text-align:center; float:right; font-size:12px;"><a href="#" id="PrintLink" onClick="return ShowPrintPopup()">Print</a></div>
<?php
						}
?>
						</td>
          </tr>
        </table></td>
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
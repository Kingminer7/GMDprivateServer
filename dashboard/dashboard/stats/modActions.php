<?php
session_start();
require "../incl/dashboardLib.php";
require "../".$dbPath."incl/lib/connection.php";
$dl = new dashboardLib();
require_once "../".$dbPath."incl/lib/mainLib.php";
require "../".$dbPath."incl/lib/exploitPatch.php";
$gs = new mainLib();
$dl->title($dl->getLocalizedString("modActions"));
$dl->printFooter('../');
$modtable = "";
$accounts = implode(",",$gs->getAccountsWithPermission("toolModactions"));
if($accounts == ""){
	$dl->printBox(sprintf($dl->getLocalizedString("errorNoAccWithPerm"), "toolsModactions"));
	exit();
}
if(!empty($_GET["search"])) {
	$query = $db->prepare("SELECT accountID, userName FROM accounts WHERE accountID IN ($accounts) AND userName LIKE '%".ExploitPatch::remove($_GET["search"])."%' ORDER BY userName ASC");
	$srcbtn = '<a href="'.$_SERVER["SCRIPT_NAME"].'" style="width: 0%;display: flex;margin-left: 5px;align-items: center;justify-content: center;color: indianred; text-decoration:none" class="btn-primary" title="'.$dl->getLocalizedString("searchCancel").'"><i class="fa-solid fa-xmark"></i></a>';
}
 else $query = $db->prepare("SELECT accountID, userName FROM accounts WHERE accountID IN ($accounts) ORDER BY userName ASC");
$query->execute();
$result = $query->fetchAll();
$row = 0;
if(empty($result)) {
	$dl->printSong('<div class="form">
    <h1>'.$dl->getLocalizedString("errorGeneric").'</h1>
    <form class="form__inner" method="post" action=".">
		<p>'.$dl->getLocalizedString("emptyPage").'</p>
        <button type="submit" class="btn-primary">'.$dl->getLocalizedString("dashboard").'</button>
    </form>
</div>', 'stats');
	die();
} 
foreach($result as &$mod){
	$row++;
	$query = $db->prepare("SELECT lastPlayed FROM users WHERE extID = :id");
	$query->execute([':id' => $mod["accountID"]]);
	$lastPlayed = $query->fetchColumn();
  	$time = $dl->convertToDate($lastPlayed);
	$query = $db->prepare("SELECT count(*) FROM modactions WHERE account = :id");
	$query->execute([':id' => $mod["accountID"]]);
	$actionscount = $query->fetchColumn();
	$query = $db->prepare("SELECT count(*) FROM modactions WHERE account = :id AND type = '1'");
	$query->execute([':id' => $mod["accountID"]]);
	$lvlcount = $query->fetchColumn();
  	if($actionscount == 0) $actionscount = '<div style="color:grey">'.$dl->getLocalizedString("noActions").'</div>';
	if($lvlcount == 0) $lvlcount = '<div style="color:grey">'.$dl->getLocalizedString("noRates").'</div>';
	if($lastPlayed == 0) $time = '<div style="color:gray">'.$dl->getLocalizedString("never").'</div>';
 	$query = $db->prepare("SELECT roleID FROM roleassign WHERE accountID =:accid");
	$query->execute([':accid' => $mod["accountID"]]);
	$resultRole = $color = implode($query->fetch());
  	$color = mb_substr($resultRole, 1);
	switch($resultRole) {
		case 11:
			$resultRole = $dl->getLocalizedString("admin");				
        break;
		case 22:
			$resultRole = $dl->getLocalizedString("elder");
			break;
		case 33:
			$resultRole = $dl->getLocalizedString("moder");
			break;
		default:
			$query = $db->prepare("SELECT roleName FROM roles WHERE roleID = :id");
			$query->execute([':id' => $color]);
			$resultRole = $query->fetch();
			$resultRole = $resultRole["roleName"];
			break;
		}
  	$query = $db->prepare("SELECT commentColor FROM roles WHERE roleID = :rid");
  	$query->execute([':rid' => $color]);
  	$color = $query->fetch();
  	$resultRole = '<div style="color:rgb('.$color["commentColor"].')">'.$resultRole.'</div>';
	if($actionscount != '<div style="color:grey">'.$dl->getLocalizedString("noActions").'</div>') {
		$actions = $actionscount[strlen($actionscount)-1];
		if($actions == 1) $action = 0; elseif($actions < 5 AND $actions != 0) $action = 1; else $action = 2;
		if($actionscount > 9 AND $actionscount < 20) $action = 2;
		$actionscount = $actionscount.' '.$dl->getLocalizedString("action$action");
	}
	if($lvlcount != '<div style="color:grey">'.$dl->getLocalizedString("noRates").'</div>') {
		$levels = $lvlcount[strlen($lvlcount)-1];
		if($levels == 1) $lvl = 0; elseif($levels < 5 AND $levels > 0) $lvl = 1; else $lvl = 2;
		if($lvlcount > 9 AND $lvlcount < 20) $lvl = 2;
		$lvlcount = $lvlcount.' '.$dl->getLocalizedString("lvl$lvl");
	}
  	$username =  '<form style="margin:0" method="post" action="profile/"><button style="margin:0" class="accbtn" name="accountID" value="'.$mod["accountID"].'">'.$mod["userName"].'</button></form>';
	$modtable .= "<tr><th scope='row'>".$row."</th><td>".$username."</td><td>".$resultRole."</td><td>".$actionscount."</td><td>".$lvlcount."</td><td>".$time."</td></tr>";
}

/* 
	printing
*/
$dl->printPage('<table class="table table-inverse">
  <thead>
    <tr>
      <th>#</th>
      <th>'.$dl->getLocalizedString("mod").'</th>
      <th>'.$dl->getLocalizedString("isAdmin").'</th>
      <th>'.$dl->getLocalizedString("count").'</th>
      <th>'.$dl->getLocalizedString("ratedLevels").'</th>
	<th>'.$dl->getLocalizedString("lastSeen").'</th>
    </tr>
  </thead>
  <tbody>
    '.$modtable.'
  </tbody>
</table><form method="get" class="form__inner">
	<div class="field" style="display:flex">
		<input style="border-top-right-radius: 0;border-bottom-right-radius: 0;" type="text" name="search" value="'.$_GET["search"].'" placeholder="'.$dl->getLocalizedString("search").'">
		<button style="width: 6%;border-top-left-radius:0px !important;border-bottom-left-radius:0px !important" type="submit" class="btn-primary" title="'.$dl->getLocalizedString("search").'"><i class="fa-solid fa-magnifying-glass"></i></button>
		'.$srcbtn.'
	</div>
</form>', true, "stats");
?>
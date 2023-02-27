<?php
session_start();
if(!isset($_SESSION['vz'])) {  $_SESSION['vz'] = '/'; } 
// config
$host = "192.168.2.127";
$port = "6600";
$spl=false;
$ssl=false;
$stop = '';
// start
$fp = fsockopen( $host, $port, $errno, $errstr, 10 );
if( ! is_resource($fp)) exit;
$initial = initialConnect($fp);
$stats = doCommand($fp,"stats");
$status = to_assoc_array(doCommand($fp,"status"));
if(!isset($status['song'])) $status['song'] = "-1"; // for when add after current song but in stop mode try to add at position 0
if(isset($_SESSION['theme'])) { $cssfile = './css/'.$_SESSION['theme'].''; } else { $cssfile = './css/default.css'; }
if(isset($_SESSION['lang'])) { 
	$langp=opendir("./lang");
	$langfiles = [];
	while (false !== ($entry = readdir($langp))) {
		if($entry != "." && $entry != "..") {
			$langfiles[] = $entry;
		}
    }
	if(in_array("lang.".$_SESSION['lang'].".php",$langfiles))
	require('./lang/lang.'.$_SESSION['lang'].'.php'); 
	else echo $_SESSION['lang']."ERROR";
} 
else require_once('./lang/lang.de_DE.php');


function lpl ($fp) {
	global $fp, $lang;
	echo '<div id="lpl"><table><tr><th colspan="2">'.$lang['playlists'].'</th></tr>';
	$lpl = doCommand($fp,'listplaylists');
	$ih=-1;
	$lh = [];
	for($i=0;$i<count($lpl);$i++) {
	if(substr($lpl[$i],0,8) == "playlist") {
		$lh[] = substr($lpl[$i],10);
	}
	}
	for($i=0;$i<count($lh);$i++) {
		echo '<tr><td><div width="100%" height="100%" onclick="javascript:location.href=\'index.php?do=showpl&arg1='.$lh[$i].'\'" title="'.$lang['lplshow'].'"><a href="index.php?do=showpl&arg1='.$lh[$i].'">'.$lh[$i].'</a></div></td>
			<td align="right"><a href="index.php?do=enqueuepl&arg1='.$lh[$i].'"><img alt="" src="./icon/pluskreis.png" class="listimg"  width="26px" height="20px"title="'.$lang['atp'].'" ></a></td>
			<td align="right"><a href="index.php?do=enqueueplacur&arg1='.$lh[$i].'"><img alt="" src="./icon/enqcur.png" class="listimg"  width="26px" height="20px"title="'.$lang['atpc'].'" ></a></td>
			<td align="right"><a href="index.php?do=enqueueaplcurplay&arg1='.$lh[$i].'"><img alt="" src="./icon/enqcurplay.png" class="listimg"  width="26px" height="20px"title="'.$lang['atpp'].'" ></a></td>
			<td align="right"><a href="index.php?do=enqueueplkillloadplay&arg1='.$lh[$i].'"><img alt="" src="./icon/enqkillplay.png" class="listimg" width="26px" height="20px" title="'.$lang['atpn'].'" ></a></td>
			</tr>';
	}
	echo '</table></div>';
}
function show($fp,$cat,$name = "") {
	global $fp, $lang;
	echo '<div id="show">';
	switch ($cat) {
		case 'search':
			$sl = doCommand($fp,'search','file',$name);
			$slsp = [];
			$nslp = -1;
			for($i=0;$i<count($sl);$i++) {
				if(substr($sl[$i],0,4)=="file")
				{
					$nslp++;
					$sl[$i] = substr($sl[$i],6);
					$slsp[$nslp]['file'] = $sl[$i];
					$slsh = substr(strrchr($sl[$i],'/'),1);
					$slsh = substr($slsh,0,strlen($slsh)-5);
					if($slsh != "") $sl[$i] = $slsh;
					$slsp[$nslp]['name'] =$sl[$i];
				}
				elseif(substr($sl[$i],0,4) == "Time")
				{
					$slsp[$nslp]['time'] = substr($sl[$i],6);
				}
			}
			echo '<table class="lplt">
				<tr><th colspan="4">'.$lang['searchres'].': "'.$name.'" ('.count($slsp).')</th></tr>
				<tr><th>#</th><th>'.$lang['name'].'</th><th>'.$lang['duration'].'</th><th>'.$lang['options'].'</th></tr>';
			for($i=0;$i<count($slsp);$i++)
			{
				echo '<tr><td class="lplt">'.$i.'</td><td class="lplt">'.$slsp[$i]['name'].'</td><td class="lplt">'.sec2format($slsp[$i]['time']).'</td>
					<td align="right"><a href="index.php?do=enqueue&arg1='.$slsp[$i]['file'].'" title="'.$lang['atp'].'"><img alt="" src="./icon/pluskreis.png" class="listimg"  width="26px" height="20px"title="'.$lang['atp'].'" ></a></td>
					<td align="right"><a href="index.php?do=enqueueacur&arg1='.$slsp[$i]['file'].'" title="'.$lang['atpc'].'"><img alt="" src="./icon/enqcur.png" class="listimg"  width="26px" height="20px"title="'.$lang['atpc'].'" ></a></td>
					<td align="right"><a href="index.php?do=enqueueacurplay&arg1='.$slsp[$i]['file'].'" title="'.$lang['atpp'].'"><img alt="" src="./icon/enqcurplay.png" class="listimg"  width="26px" height="20px"title="'.$lang['atpp'].'" ></a></td>
					<td align="right"><a href="index.php?do=enqueuekilladdplay&arg1='.$slsp[$i]['file'].'" title="'.$lang['atpn'].'"><img alt="" src="./icon/enqkillplay.png" class="listimg" width="26px" height="20px" title="'.$lang['atpn'].'" ></a></td>
					</tr>';
			}
			echo '</table>';
			break;
		case 'pl':
			$lpls = doCommand($fp,'listplaylistinfo',$name);
			echo '<table class="lplt">
			<tr><th colspan="4"><a href="index.php?do=load&arg1='.$name.'">+++'.$lang['playlist'].' '.$name.' '.$lang['loadasactive'].'+++</a></th></tr>
			<tr><th>#</th><th>'.$lang['name'].'</th><th>'.$lang['duration'].'</th><th>'.$lang['options'].'</th></tr>';
			if($lpls != "") { //empty playlist
				$lplp = [];
				$nlp = -1;
				for($i=0;$i<count($lpls);$i++) {
					if(substr($lpls[$i],0,4)=="file")
					{
						$nlp++;
						$lpls[$i] = substr($lpls[$i],6);
						$lplp[$nlp]['file'] = $lpls[$i];
						$lplsh = substr(strrchr($lpls[$i],'/'),1);
						$lplsh = substr($lplsh,0,strlen($lplsh)-5);
						if($lplsh != "") $lpls[$i] = $lplsh;
						$lplp[$nlp]['name'] =$lpls[$i];
					}
					elseif(substr($lpls[$i],0,4) == "Time")	{
						$lplp[$nlp]['time'] = substr($lpls[$i],6);
					}
				}
				for($i=0;$i<count($lplp);$i++) {
					echo '<tr><td class="lplt">'.$i.'</td><td class="lplt">'.$lplp[$i]['name'].'</td><td class="lplt">'.sec2format($lplp[$i]['time']).'</td>
						<td align="right"><a href="index.php?do=enqueue&arg1='.$lplp[$i]['file'].'" title="'.$lang['atp'].'"><img alt="" src="./icon/pluskreis.png" class="listimg"  width="26px" height="20px"title="'.$lang['atp'].'"></a></td>
						<td align="right"><a href="index.php?do=enqueueacur&arg1='.$lplp[$i]['file'].'" title="'.$lang['atpc'].'"><img alt="" src="./icon/enqcur.png" class="listimg"  width="26px" height="20px"title="'.$lang['atpc'].'"></a></td>
						<td align="right"><a href="index.php?do=enqueueacurplay&arg1='.$lplp[$i]['file'].'" title="'.$lang['atpp'].'"><img alt="" src="./icon/enqcurplay.png" class="listimg"  width="26px" height="20px"title="'.$lang['atpp'].'"></a></td>
						<td align="right"><a href="index.php?do=enqueuekilladdplay&arg1='.$lplp[$i]['file'].'" title="'.$lang['atpn'].'"><img alt="" src="./icon/enqkillplay.png" class="listimg" width="26px" height="20px" title="'.$lang['atpn'].'"></a></td>		 
						</tr>';
				}
			}
			echo '</table>';
			break;
		case 'vz':
			echo '<table class="vzt" width="100%" cellspacing="0" cellpadding="0">
				<tr><td colspan="4" style="padding-left:10px;border-style:solid;border:0px;border-bottom:1px;"><b>'.$lang['musicdir'].'</b></td></tr>
				<tr><th></th><th class="vzth">'.$lang['name'].'</th><th class="vzth">'.$lang['duration'].'</th><th class="vzth" colspan="4">'.$lang['options'].'</th></tr>';
				$d = strlen(strrchr($_SESSION['vz'],'/'));
				$dd = (strlen($_SESSION['vz'])-$d);
				$dirup = substr($_SESSION['vz'],0,$dd);
				if($d == "0") $dirup = '/';
				if($_SESSION['vz'] != '/') { echo '<tr><td class="vztd" colspan="3" title="'.$lang['goup'].'"><a href="index.php?do=showvz&arg1='.$dirup.'"><div style="width:100%;height:100%"><b>..'.$lang['goup'].'..</b></div></a></td></tr>'; }
				$lvz = doCommand($fp,'lsinfo',$_SESSION['vz']);
				for($i=0;$i<count($lvz);$i++) {
					if(substr($lvz[$i],0,9) == 'directory') {
						$lvzshowname = ($_SESSION['vz'] == "/") ? substr($lvz[$i],11) : trim(substr(strrchr($lvz[$i],'/'),1));
						echo '<tr><td class="vztd" width="40px"> <img alt="" src="./icon/folder.png"  height="20px"> </td>
							<td class="vztd"><a href="index.php?do=showvz&arg1='.trim(substr($lvz[$i],11)).'">
							<div style="width:100%;height:100%">'.$lvzshowname.'</div>
							</a></td>
							<td class="vztd"></td>
							<td align="right"><a href="index.php?do=enqueue&arg1='.trim(substr($lvz[$i],11)).'" title="'.$lang['atp'].'"><img alt="" src="./icon/pluskreis.png" class="listimg"  width="26px" height="20px"title="'.$lang['atp'].'"></a></td>
							<td align="right"><a href="index.php?do=enqueueacur&arg1='.trim(substr($lvz[$i],11)).'" title="'.$lang['atpc'].'"><img alt="" src="./icon/enqcur.png" class="listimg"  width="26px" height="20px"title="'.$lang['atpc'].'"></a></td>
							<td align="right"><a href="index.php?do=enqueueacurplay&arg1='.trim(substr($lvz[$i],11)).'" title="'.$lang['atpp'].'"><img alt="" src="./icon/enqcurplay.png" class="listimg"  width="26px" height="20px"title="'.$lang['atpp'].'"></a></td>
							<td align="right"><a href="index.php?do=enqueuekilladdplay&arg1='.trim(substr($lvz[$i],11)).'" title="'.$lang['atpn'].'"><img alt="" src="./icon/enqkillplay.png" class="listimg" width="26px" height="20px" title="'.$lang['atpn'].'"></a></td>				
							</tr>';
					}
					elseif(substr($lvz[$i],0,4) == 'file') {
						$lvzshowname = ($_SESSION['vz'] == "/") ? substr($lvz[$i],6) : trim(substr(strrchr($lvz[$i],'/'),1));
						echo '<tr><td class="vztd" width="40px"> <img alt="" src="./icon/music.png" height="20px"> </td><td class="vztd"> '.$lvzshowname.'</td><td class="vztd" title="'.$lang['duration'].'">';
						$lvzfilehelp = substr($lvz[$i],6);
					}
					elseif(substr($lvz[$i],0,4) == 'Time')
					{
						echo sec2format(substr($lvz[$i],6)).'</td>
						<td align="right"><a href="index.php?do=enqueue&arg1='.$lvzfilehelp.'" title="'.$lang['atp'].'"><img alt="" src="./icon/pluskreis.png" class="listimg"  width="26px" height="20px"title="'.$lang['atp'].'"></a></td>
						<td align="right"><a href="index.php?do=enqueueacur&arg1='.$lvzfilehelp.'" title="'.$lang['atpc'].'"><img alt="" src="./icon/enqcur.png" class="listimg"  width="26px" height="20px"title="'.$lang['atpc'].'"></a></td>
						<td align="right"><a href="index.php?do=enqueueacurplay&arg1='.$lvzfilehelp.'" title="'.$lang['atpp'].'"><img alt="" src="./icon/enqcurplay.png" class="listimg"  width="26px" height="20px"title="'.$lang['atpp'].'"></a></td>
						<td align="right"><a href="index.php?do=enqueuekilladdplay&arg1='.$lvzfilehelp.'" title="'.$lang['atpn'].'"><img alt="" src="./icon/enqkillplay.png" class="listimg" width="26px" height="20px" title="'.$lang['atpn'].'"></a></td>					  
						</tr>';
					}
				}
				echo '</table>';
				break;
		default:
			break;
	}
	echo '</div>';
}
function stats($fp) {
	global $fp, $lang;
	$stats = doCommand($fp,'stats');
	$stat['artists'] = trim(substr($stats[0],9));
	$stat['songs'] = trim(substr($stats[2],7));
	$stat['uptime'] = trim(substr($stats[3],8));
	$stat['db_playtime'] = trim(substr($stats[5],13));
	$stat['db_update'] = trim(substr($stats[6],11));
	$stat['playtime'] = trim(substr($stats[4],10));
	echo '<div id="stats">';
	echo '<table><tr><td>'.$lang['artists'].': # '.$stat['artists'].'</td><td>'.$lang['titel'].': # '.$stat['songs'].'</td></tr>';
	echo '<tr><td>'.$lang['tpt'].': '.sec2format($stat['db_playtime'],'full').'</td><td>'.$lang['lastupdate'].': '.date('d.m.Y H:i:s',$stat['db_update']).'</td></tr></table>';
	echo '<div>';
}
function cpl ($fp,$status) {
	global $fp, $lang;
	if($status['playlistlength'] > 0 ) {
	$playlist = doCommand($fp,'playlistinfo');
	$it=-1;
	$list = [];
	for($i=0;$i<count($playlist);$i++) {
		if(substr($playlist[$i],0,4) == "file") {
		$it++;
		$field = stristr($playlist[$i],":",true);
		$value = trim(stristr($playlist[$i]," ",false));
		$list[$it][$field] = $value;  
		$name = trim(substr(strrchr($playlist[$i],"/"),1));
		if($name == "") $name = trim(substr($playlist[$i],6));
		$list[$it]['name'] = substr($name, 0, strlen($name)-4);
		}
		else {
			$field = stristr($playlist[$i],":",true);
			$value = trim(stristr($playlist[$i]," ",false));
			$list[$it][$field] = $value;
		}
	}
	echo '<div id="cpl">
		<table class="playlist" cellspacing="0" cellpadding="0">
		<tr><th class="pth">#</th><th class="pth">'.$lang['name'].'</th><th class="pth" title="'.$lang['delete'].'" width="25px">'.$lang['delete'].'</th><th class="pth" width="50px" title="'.$lang['duration'].'">'.$lang['duration'].'</th><th class="pth" colspan="2">'.$lang['move'].'</th></tr>';

	for($i=0;$i<count($list);$i++) {
		if(isset($status['song'])){ 
			if($list[$i]['Pos'] == $status['song']) {
				$plcsbg = 'active'; 
			}
			else {
				$plcsbg = ''; 
			}
		}	
		else {
			$plcsbg = '';
		}
		echo '<tr><td class="ptdp '.$plcsbg.'" width="30px" >'.$list[$i]['Pos'].'</td>
			<td id="cplsel'.$list[$i]['Pos'].'" class="ptdn '.$plcsbg.'" onclick="javascript:cplselectadd(this.id);" ondblclick="javascript:location.href=\'index.php?do=play&arg1='.$list[$i]['Pos'].'\'">';
 
		if(isset($list[$i]['Artist']) && $list[$i]['Artist'] != "" && isset($list[$i]['Title']) && $list[$i]['Title'] != "") echo $list[$i]['Artist']." - ".$list[$i]['Title'];
		else echo $list[$i]['name'];
		echo '</td>';
		echo '<td class="ptd ptdd '.$plcsbg.'" style="text-align:center;"><a href="index.php?do=delete&arg1='.$list[$i]['Pos'].'"><img alt="" class="img" src="./icon/del.png" height="10px" title="'.$lang['delfl'].'"></a></td>';
		echo '<td class="ptd '.$plcsbg.'" style="text-align:center;">'.sec2format($list[$i]['Time']).'</td>';
		echo '<td class="ptd ptdm '.$plcsbg.'" style="">
			<a href="index.php?do=move&arg1='.$list[$i]['Pos'].'&arg2='.($list[$i]['Pos']-1).'"><img alt="" class="img" src="./icon/up.png" height="20px" title="'.$lang['moveup'].'"></a>
			</td><td class="ptd '.$plcsbg.'" style=""> 
			<a href="index.php?do=move&arg1='.$list[$i]['Pos'].'&arg2='.($list[$i]['Pos']+1).'"><img alt="" class="img" src="./icon/down.png" height="20px" title="'.$lang['movedown'].'"></a>
			</td></tr>';
	}
	echo '</table>';
	echo '</div>';
	}
}
function pctrl ($fp,$status) {
	global $fp, $lang;
	$volup = ($status['volume'] > 99) ? 100 : ($status['volume']+1);
	$voldown = ($status['volume'] < 1) ? 0 : ($status['volume']-1);
	$player['play'] = ($status['state'] == "play") ? '<img alt="" src="./icon/playac.jpg" class="ctrlimg" title="'.$lang['playac'].'" onclick="location.href=\'index.php?do=play\'">' : '<img alt="" src="./icon/play.png" class="ctrlimg" title="'.$lang['play'].'" onclick="location.href=\'index.php?do=play\'">';
	$player['previous'] = '<img alt="" src="./icon/backend.png" class="ctrlimg" title="'.$lang['previous'].'" onclick="location.href=\'index.php?do=previous\'">';
	$player['next'] = '<img alt="" src="./icon/frontend.png" class="ctrlimg" title="'.$lang['next'].'" onclick="location.href=\'index.php?do=next\'">';
	$player['stepback'] = '<img alt="" src="./icon/stepback.png" class="ctrlimg" title="'.$lang['sec5m'].'" onclick="location.href=\'index.php?do=seekset&arg1=-5\'">';
	$player['stepfor'] = '<img alt="" src="./icon/stepfor.png" class="ctrlimg" title="'.$lang['sec5p'].'" onclick="location.href=\'index.php?do=seekset&arg1=5\'">';
	$player['volup'] = '<img alt="" src="./icon/volup.png" class="ctrlimg" title="'.$lang['volup'].'" onclick="location.href=\'index.php?do=setvol&arg1='.$volup.'\'">';
	$player['voldown'] = '<img alt="" src="./icon/voldown.png" class="ctrlimg" title="'.$lang['voldown'].'" onclick="location.href=\'index.php?do=setvol&arg1='.$voldown.'\'">';
	if($status['volume'] == 0) {
		$player['volmute'] = '<img alt="" src="./icon/volmuteac.jpg" class="ctrlimg" title="'.$lang['muteac'].'" onclick="location.href=\'index.php?do=setvol&arg1=99\'">';
	}
	else {
		$player['volmute'] = '<img alt="" src="./icon/volmute.png" class="ctrlimg" title="'.$lang['mute'].'" onclick="location.href=\'index.php?do=setvol&arg1=0\'">';
	}
	if($status['repeat'] == 0) {
		$player['repeat'] = '<img alt="" src="./icon/repeat.png" class="ctrlimg" title="'.$lang['repeat'].'" onclick="location.href=\'index.php?do=repeat&arg1=1\'">';
	}
	else {
		$player['repeat'] = '<img alt="" src="./icon/repeatac.jpg" class="ctrlimg" title="'.$lang['repeatac'].'" onclick="location.href=\'index.php?do=repeat&arg1=0\'">';
	}
	if($status['random'] == 0) {
		$player['random'] = '<img alt="" src="./icon/recycle.png" class="ctrlimg"  title="'.$lang['random'].'" onclick="location.href=\'index.php?do=random&arg1=1\'">';
	}
	else {
		$player['random'] = '<img alt="" src="./icon/recycleac.jpg" class="ctrlimg"  title="'.$lang['randomac'].'" onclick="location.href=\'index.php?do=random&arg1=0\'">';
	}
	if($status['single'] == 0) {
		$player['single'] = '<img alt="" src="./icon/single.png" class="ctrlimg" title="'.$lang['single'].'" onclick="location.href=\'index.php?do=single&arg1=1\'" onmouseover="javascript:tooltip_on(this,\'When single is activated, playback is stopped after current song, or song is repeated if the repeat mode is enabled.\');" onmouseout="javascript:tooltip_off();">';
	}
	else {
		$player['single'] = '<img alt="" src="./icon/singleac.jpg" class="ctrlimg" title="'.$lang['singleac'].'" onclick="location.href=\'index.php?do=single&arg1=0\'" onmouseover="javascript:tooltip_on(this,\'When single is activated, playback is stopped after current song, or song is repeated if the repeat mode is enabled.\');" onmouseout="javascript:tooltip_off();">';
	}
	if($status['consume'] == 0) {
		$player['consume'] = '<img alt="" src="./icon/fadenkreuz.png" class="ctrlimg" title="'.$lang['consume'].'" onclick="location.href=\'index.php?do=consume&arg1=1\'" onmouseover="javascript:tooltip_on(this,\'When consume is activated, each song played is removed from playlist.\');" onmouseout="javascript:tooltip_off();">';
	}
	else {
		$player['consume'] = '<img alt="" src="./icon/fadenkreuzac.jpg" class="ctrlimg" title="'.$lang['consumeac'].'" onclick="location.href=\'index.php?do=consume&arg1=0\'" onmouseover="javascript:tooltip_on(this,\'When consume is activated, each song played is removed from playlist.\');" onmouseout="javascript:tooltip_off();">';
	}			
	if($status['state'] == "stop") {
		$player['stop'] = '<img alt="" src="./icon/stopac.jpg" class="ctrlimg" title="'.$lang['stop'].'">';
		$player['pause'] = '<img alt="" src="./icon/pause.png" class="ctrlimg" title="'.$lang['pause'].'">';
		$player['title'] = '';
		$player['artist'] = '';
		$player['position'] = '';
		$player['times'] = '<span id="showelapsed">0</span> / 0:00';
		$player['duration'] = 1;
		$player['elapsed'] = 0;
	}
	else {
		$status['time'] = sec2format(strstr($status['time'],":",true))."/".sec2format(substr(strrchr($status['time'],":"),1));
		$cursong = to_assoc_array(doCommand($fp,"playlistinfo ".$status['song']));							
		if(!isset($cursong['Artist']) && !isset($cursong['Title'])) {
			$cursong['title'] = trim(substr(strrchr($cursong['file'],"/"),1)); //get rid of parent dirs
			if($cursong['title'] == "") $cursong['title'] = $cursong['file']; //if no parent dir
			$cursong['title'] = substr($cursong['title'], 0, strlen($cursong['title'])-4); //get rid of file extension
			$cursong['artist'] = '';
		}
		$cursong['duration'] = $cursong['Time']; if($cursong['duration'] == 0) $cursong['duration'] = 24*3600; //for streams set dur=1day
		$cursong['pos'] = $cursong['Pos'];
		$cursong['id'] = $cursong['Id'];
		$player['elapsed'] = $status['elapsed'];
		$player['duration'] = $cursong['duration'];
		$player['times'] = '<span id="showelapsed">'.sec2format($status['elapsed']).'</span> / '.sec2format($cursong['duration']);
		$player['position'] = $cursong['id'];
		$player['title'] = (!isset($cursong['Title'])) ? $cursong['title'] : $cursong['Title'];
		$player['artist'] = (!isset($cursong['Artist'])) ? '<br>' : "von ".$cursong['Artist'];
		$player['stop'] = '<img alt="" src="./icon/stop.png" class="ctrlimg" title="'.$lang['stop'].'" onclick="javascript:window.location.href=\'index.php?do=stop\'">';
		$player['pause'] = ($status['state'] == "pause") ? '<img alt="" src="./icon/pauseac.jpg" class="ctrlimg" title="'.$lang['pauseac'].'" onclick="javascript:window.location.href=\'index.php?do=pause\'">' : '<img alt="" src="./icon/pause.png" class="ctrlimg" title="Pause" onclick="javascript:window.location.href=\'index.php?do=pause\'">';
	}
	if($status['xfade'] == 0) {
		$player['xfade'] = '<img alt="" src="./icon/crossfade.png" class="ctrlimg" title="'.$lang['xfade'].'" onclick="javascript:alert(\'do crossfade popup\');">';
	}
	else {
		$player['xfade'] = '<img alt="" src="./icon/crossfadeac.jpg" class="ctrlimg" title="'.$lang['xfadeac'].' '.$status['xfade'].'" onclick="javascript:alert(\'do crossfade popup\');">';
	}
	return $player;
}
function sec2format($sec,$mode="") {
		$min = floor($sec / 60);
		$sec = (($sec/60)-$min)*60;
		
		
		substr($sec,2);
		if($mode == 'full')
		{
		 $H = floor($min / 60);
		 $M = (($min/60)-$H)*60;
		 $D = floor($H / 24);
		 $H = (($H/24)-$D)*24;
		 $S = $sec;
		 $D = round($D,0);
		 $H = round($H,0);
		 $M = round($M,0);
		 $S = round($S,0);
		 return $D.'T '.$H.' h '.$M.' min '.$S.' sek';
		}
		else
		{
		 if(round($sec,0) < 10) $sec = "0".round($sec,0);
		 return round($min,0).":".$sec;
		}
}
function to_assoc_array($arr) {
	$ret = [];
	for($i=0;$i<count($arr);$i++) {
	 $field = stristr($arr[$i],":",true);
	 $value = trim(stristr($arr[$i]," ",false));
	 $ret[$field] = $value;
	}
	return $ret;
}
function initialConnect( $fp ) {
	while( ! feof( $fp )) 	{
		$got =  fgets( $fp, "1024" );
		if( strncmp( "OK", $got, strlen( "OK" )) == "0" )
		{
		        return preg_replace( "/^OK MPD /", "", $got );
		}
		if( strncmp( "ACK", $got, strlen( "ACK" )) == "0")
		{
			return $got;
		}
	}
}
function doCommand($fp,$command,$arg1 = "",$arg2 = "") {
	global $fp, $lang;
	if($command == "seek") {
		$z = to_assoc_array(doCommand($fp,"status"));
		$h = to_assoc_array(doCommand($fp,"playlistinfo ".$z['song']));
		$arg1 = $z['song']." ".floor($arg1*$h['Time']);
		$command.= ' '.$arg1;
	}
	else {
		if($command == "seekset"){
			$command = "seek";
		}
		$staus = [];
		$arg1 = urldecode($arg1);
		$arg2 = urldecode($arg2);
		if($arg1 != "") { $command.=  ' "'.$arg1.'"'; }
		if($arg2 != "") { $command.= ' "'.$arg2.'"'; }
	}
	fputs($fp,$command."\n");
	while( ! feof( $fp ))
	{
	    $got = fgets( $fp, "1024" );
		$status[] = $got;
		if( strncmp( "OK", $got, strlen( "OK" )) == "0" ) 
		{
		        break;
		}
		str_replace( "\n", "\n<br>", $got );
		if ( strncmp( "ACK", $got, strlen( "ACK" )) == "0" ) 
		{
		 $status = "";
		        break;
		}
	}
	return $status;
}
function langselect() {
	$langp=opendir("./lang");
	$langfiles = [];
	while (false !== ($entry = readdir($langp))) {
		if($entry != "." && $entry != "..") {
			$langfiles[] = $entry;
		}
    }
	closedir($langp);
	
	echo '<select id="lang" onchange="javascript:switchlang();">';
	echo '<option> </option>';
	for($i=0;$i<count($langfiles);$i++) {
		$langactive = ("lang.".$_SESSION['lang'].".php" == $langfiles[$i]) ? "selected" :  "";
		echo '<option id="'.$langfiles[$i].'" '.$langactive.'>'.$langfiles[$i].'</option>';
	}
	echo '</select>';
}
function themeselect() {
	$langp=opendir("./css");
	$langfiles = [];
	while (false !== ($entry = readdir($langp))) {
		if($entry != "." && $entry != "..") {
			$langfiles[] = $entry;
		}
    }
	closedir($langp);
	
	echo '<select id="theme" onchange="javascript:switchtheme();">';
	echo '<option> </option>';
	for($i=0;$i<count($langfiles);$i++) {
		$langactive = ($_SESSION['theme'].".css" == $langfiles[$i]) ? "selected" :  "";
		echo '<option id="'.$langfiles[$i].'" '.$langactive.'>'.substr($langfiles[$i],0,strlen($langfiles[$i])-4).'</option>';
	}
	echo '</select>';
}
function setbgcolor() {
	if(isset($_SESSION['bgcolor'])) { $bgc = $_SESSION['bgcolor']; } else { $bgc = ""; }
	echo '<input type="text" value="'.$bgc.'" id="bgcolorcode"><input type="button" value="ok" onclick="javascript:setbgcolor();">';
}
if(isset($_GET['do'])) {
	$arg1 = isset($_GET['arg1']) ? $_GET['arg1'] : "";
	$arg2 = isset($_GET['arg2']) ? $_GET['arg2'] : "";
	if($_GET['do'] == "enqueue"){
		doCommand($fp,'add',$arg1);
	}
	elseif($_GET['do'] == "bgset") {
		if($arg1 != "") {
			$_SESSION['bgcolor'] = $arg1;
			header('Location: index.php');
		}
	}
	elseif($_GET['do'] == "enqueueacur") {
		doCommand($fp,'addid',$arg1,($status['song']+1)); //add
	}
	elseif($_GET['do'] == "enqueueacurplay") {
		
		doCommand($fp,'addid',$arg1,($status['song']+1)); //add
		if($status['song'] == "-1")
			doCommand($fp,'play');
		else
			doCommand($fp,'next');
	}
	elseif($_GET['do'] == "enqueuekilladdplay"){
		doCommand($fp,'clear');
		doCommand($fp,'add',$arg1);
		doCommand($fp,'play');
	}
	elseif($_GET['do'] == "enqueuepl"){
		doCommand($fp,'load',$arg1);
	}
	elseif($_GET['do'] == "enqueueplacur" || $_GET['do'] == "enqueueplacurplay") {
		$l = doCommand($fp,'listplaylist',$arg1);
		for($i=0;$i<count($l);$i++)
		{
			$l[$i] = trim(substr($l[$i],6));
		}
		$l = array_reverse($l);
		for($i=0;$i<count($l);$i++)
		{
			doCommand($fp,'addid',$l[$i],($status['song']+1)); //add
		}
		if($_GET['do'] == "enqueueplacurplay")
		{
			doCommand($fp,'next');
		}
	}
	elseif($_GET['do'] == "enqueueplkillloadplay")
	{
		doCommand($fp,'clear');
		doCommand($fp,'load',$arg1);
		doCommand($fp,'play');
		sleep(1);
	}
	elseif($_GET['do'] == "showpl")
	{
		$arg = rawurldecode($arg1);
		$spl=true;
	}
	elseif($_GET['do'] == "search")
	{
		$sarg = rawurldecode($arg1);
		$ssl=true;
	}
	elseif($_GET['do'] == "showvz")
	{
		$arg = rawurldecode($arg1);
		if($arg != "") $_SESSION['vz'] =$arg;
	}
	elseif($_GET['do'] == "seekset") 
	{
		$arg1 = (int)$arg1;
		$s = doCommand($fp,'status');
		$s = to_assoc_array($s);
		$seekoffset = $s['elapsed'];
		doCommand($fp,'seekset',$s['song'],round($seekoffset+$arg1,0));
	}
	elseif($_GET['do'] == "move") 
	{
	 if($arg1 == "up") {
	  $posids = explode(",",$arg2);
	  sort($posids,SORT_NUMERIC); //walk through positions from top
	  for($i=0;$i<count($posids);$i++){
	   doCommand($fp,'move',$posids[$i],($posids[$i] - 1));
	  }
	 }
	 elseif($arg1 == "down") {
	  $posids = explode(",",$arg2);
	  sort($posids,SORT_NUMERIC);
	  $posids = array_reverse($posids); //walk through positions from bottom
	  for($i=0;$i<count($posids);$i++){
	   doCommand($fp,'move',$posids[$i],($posids[$i] + 1));
	  }
	 }
	 else {
	  doCommand($fp,'move',$arg1,$arg2);
	 }
	}
	elseif($_GET['do'] == "delete") {
		if($arg1 == "list") {
			$posids = explode(",",$arg2);
			sort($posids,SORT_NUMERIC); //ascending order
			for($i=0;$i<count($posids);$i++){
				doCommand($fp,'delete',$posids[$i]-$i); // for each delete the positions changed -1 therefore correct the remaining positions
			}
		}
		else {
			doCommand($fp,'delete',$arg1);
		}
	}
	elseif($_GET['do'] == "lang") {
		if($arg1 != "") {
			$_SESSION['lang'] = $arg1;
			header("Location: index.php");	
		}
	}
	elseif($_GET['do'] == "theme") {
		if($arg1 != "") {
			$_SESSION['theme'] = $arg1;
			header("Location: index.php");
		}
	}
	else
	{
		$cmd= doCommand($fp,$_GET['do'],$arg1,$arg2);
		sleep(1);
	}
	$status = to_assoc_array(doCommand($fp,"status")); //status update after action
}

?>
<html>
	<head>
	<link rel="Stylesheet" type="text/css" href="<?php echo $cssfile; ?>">
<script type="text/javascript">
	var cplselects = new Array();
	function seekhelpon() {
		document.getElementById('seekcoords').style.display='block';
	}
	function switchlang() {
	 var lang = document.getElementById('lang').value;
	 lang = lang.substr(5,5);
	 window.location.href="index.php?do=lang&arg1="+lang;
	}
	function switchtheme() {
	 var theme = document.getElementById('theme').value;
	 window.location.href="index.php?do=theme&arg1="+theme;
	}
	function setbgcolor() {
	 var code = document.getElementById('bgcolorcode').value
	 window.location.href="index.php?do=bgset&arg1="+code;
	}
	function seekupdate(e,dur) {
		var posX = e.clientX;
		var ox = document.getElementById('seekbar').offsetParent.offsetLeft+document.getElementById('seekbar').offsetLeft;
		var ow = document.getElementById('seekbar').offsetWidth;
		var secs = (posX-ox)/(ow)*dur;
		var min = Math.floor(secs/60);
		var sec = Math.floor(((secs/60)-min)*60);
		if (sec < 10) sec = "0"+sec;
		document.getElementById('seekcoords').style.left=e.clientX-ox+15;
		document.getElementById('seekcoords').innerHTML = min+":"+sec;
	}
	function seekhelpoff() {
		document.getElementById('seekcoords').style.display='none';
	}
	function seeksend(e) {
	    var posX = e.clientX;
		var posY = e.clientY;
		var ox = document.getElementById('seekbar').offsetParent.offsetLeft+document.getElementById('seekbar').offsetLeft;
		var ow = document.getElementById('seekbar').offsetWidth;
		var prozent = (posX-ox)/(ow);
		window.location.href='index.php?do=seek&arg1='+prozent;
	}
	function progressbar() {
		if(document.getElementById('playstatus').value == 'play') {
			mw = document.getElementById('seekbar').style.width;
			cw = document.getElementById('seekelapsed').style.width;
			te = Math.round(Number(document.getElementById('timeelapsed').value),0);
			tm = Number(document.getElementById('timemax').value);
			document.getElementById('seekelapsed').style.width = (te/tm)*100+"%";
			if(te < tm) {
				te=te+1
				tsmin = Math.floor((te/60));
				tssec = Math.round(((te/60)-Math.floor((te/60)))*60,0);
				if(tssec < 10) { tssec = "0"+tssec; }
					tshow = tsmin+":"+tssec;
					document.getElementById('timeelapsed').value = te;
					document.getElementById('showelapsed').innerHTML = tshow;
			}
			if(te == tm) {
				window.location.href='index.php';
			}
		}
	}
	function crossfadeset() {
		var xfade = Number(document.getElementById('crossval').value);
		if(xfade < 0) { xfade = 0; }
		window.location.href='index.php?do=crossfade&arg1='+xfade;
	}
	function saveplaylistnow() {
		var name = document.getElementById('playlistsavename').value;
		window.location.href='index.php?do=save&arg1='+name;
	}
	function clearplaylistnow() {
		window.location.href='index.php?do=clear';
	}
	function searchsend() {
		var s = document.getElementById('searchf').value;
		window.location.href='index.php?do=search&arg1='+s;
}
	function cplselectadd(id) {
		if(cplselects.indexOf(id) =="-1") {
			document.getElementById(id).style.backgroundColor='#ddd';
			cplselects[cplselects.length] = id;
		}
		else {
			document.getElementById(id).style.backgroundColor='';
			cplselects[cplselects.indexOf(id)] = '';
		}
	}
	function cplselectsenddel() {
		var x = '';
		for(i=0;i<cplselects.length;i++){
			if(x == '') {x=(cplselects[i].substr(6,cplselects[i].length));}
			else { x = x+","+(cplselects[i].substr(6,cplselects[i].length)); }
		}
		window.location.href="index.php?do=delete&arg1=list&arg2="+x;
	}
	function cplselectsendup() {
		var x = '';
		for(i=0;i<cplselects.length;i++){
			if(x == '') {x=(cplselects[i].substr(6,cplselects[i].length));}
			else { x = x+","+(cplselects[i].substr(6,cplselects[i].length)); }
		}
		window.location.href="index.php?do=move&arg1=up&arg2="+x;
	}
	function cplselectsenddown() {
		var x = '';
		for(i=0;i<cplselects.length;i++){
			if(x == '') {x=(cplselects[i].substr(6,cplselects[i].length));}
			else { x = x+","+(cplselects[i].substr(6,cplselects[i].length)); }
		}
		window.location.href="index.php?do=move&arg1=down&arg2="+x;
	}
	function cplselectsendplay() {
		if(cplselects.length == 1)
		{
			var x = (cplselects[0].substr(6,cplselects[0].length));
			window.location.href="index.php?do=play&arg1="+x;
		}
	}

	function init() {
		setConstructSize();
		window.setInterval("progressbar()",1000);
	}
	function setConstructSize() { 
		//3-row-design: 150:*:100 fixed position
		var maxw = document.body.clientWidth; 
		var maxh = document.body.clientHeight;
		var head = document.getElementById('header');
		var cont = document.getElementById('content');
		var foot = document.getElementById('footer')
		head.style.width=maxw;
		head.style.height=150;
		head.style.overflow='auto';
		head.style.top=0;
		head.style.border='solid 0px';
		head.style.position='absolute';
		foot.style.width=maxw;
		foot.style.height=100;
		foot.style.top=maxh-100;
		foot.style.overflow='auto';
		foot.style.border='solid 0px';
		foot.style.position='absolute';
		cont.style.height=maxh-250;
		cont.style.width=maxw;
		cont.style.top=150;
		cont.style.overflow='auto';
		cont.style.border='solid 0px';
		cont.style.position='absolute';
		setContentFramesSizes();
	}
	function setContentFramesSizes() {
		var cont = document.getElementById('content');
		var head = document.getElementById('header');
		var cl = document.getElementById('cl');
		var cm = document.getElementById('cm');
		var cr = document.getElementById('cr');
		var clphead = document.getElementById('clphead');
		var sc = document.getElementById('shortcuts');
		cl.style.overflow='auto';
		cm.style.overflow='auto';
		cr.style.overflow='auto';
		cl.style.width=(cont.style.width.substr(0,(cont.style.width.length-2))/3)-17;
		cm.style.width=(cont.style.width.substr(0,(cont.style.width.length-2))/3)-17;
		cr.style.width=(cont.style.width.substr(0,(cont.style.width.length-2))/3)-17;
		cplhead.style.width=(cont.style.width.substr(0,(cont.style.width.length-2))/3)-17;
		sc.style.width=(cont.style.width.substr(0,(cont.style.width.length-2))/3)-17;
		cl.style.height=(cont.style.height.substr(0,(cont.style.height.length-2))-17);
		cm.style.height=(cont.style.height.substr(0,(cont.style.height.length-2))-17);
		cr.style.height=(cont.style.height.substr(0,(cont.style.height.length-2))-17);
		cl.style.left=0;
		sc.style.left=0;
		cm.style.left=((cl.style.width.substr(0,cl.style.width.length-2))*1)+17;
		cr.style.left=((cl.style.width.substr(0,cl.style.width.length-2))*2)+34;
		cplhead.style.left=((cl.style.width.substr(0,cl.style.width.length-2))*2)+34;
		cl.style.position='absolute';
		cm.style.position='absolute';
		cr.style.position='absolute';
		cplhead.style.position='absolute';
		sc.style.position='absolute';
		cl.style.padding=5;
		cm.style.padding=5;
		cr.style.padding=5;
		cl.style.display='block';
		cm.style.display='block';
		cr.style.display='block';
		cplhead.style.display='inline';
		sc.style.display='inline';
		document.getElementById('playlistsavename').style.display='none';
		document.getElementById('playlistsave').style.display='none';
		cplhead.style.top=(head.style.height.substr(0,(head.style.height.length-2))*1)-cplhead.clientHeight;
		sc.style.top=(head.style.height.substr(0,(head.style.height.length-2))*1)-sc.clientHeight;
		playlisten();
	}
	function playlisten () {
		var cl = document.getElementById('cl');
		var cm = document.getElementById('cm');
		var cr = document.getElementById('cr');
		if(cl.style.display=='block') {
			cl.style.display='none';
			cm.style.left='0';
			cm.style.width=(cl.style.width.substr(0,cl.style.width.length-2))*2;
		}
		else{
			cl.style.display='block';
			cm.style.left=(cl.style.width.substr(0,cl.style.width.length-2))
			cm.style.width=cl.style.width;
		}
	}
	function suche() {
		var sf = document.getElementById('searchf');
		var ss = document.getElementById('searchs');
		if(ss.style.display=='none'){
			ss.style.display='inline';
			sf.style.display='inline';
		}
		else{
			ss.style.display='none';
			sf.style.display='none';
		}
	}
	function playlistsave() {
		if(document.getElementById('playlistsave').style.display=='inline'){
			document.getElementById('playlistsave').style.display='none';
			document.getElementById('playlistsavename').style.display='none';	 
		}
		else{
			document.getElementById('playlistsave').style.display='inline';
			document.getElementById('playlistsavename').style.display='inline';	 
		}
	}
	function auswahl(fok) {
		document.getElementById(fok).style.backgroundcolor='#000000';		
	}
	function collapseplayer() {
		if(document.getElementById('playersizer').style.right=="70px") {
			var head = document.getElementById('header');
			document.getElementById('player').style.width="400px";
			document.getElementById('playersizer').style.right=(head.style.width.substr(0,(head.style.width.length-2))-470)+"px";
			document.getElementById('playersizerimg').src='./icon/forward.png';
			document.getElementById('playersizerimg').title="Expand Player";
		}
		else {
			document.getElementById('player').style.width='';
			document.getElementById('playersizer').style.right="70px";
			document.getElementById('playersizerimg').src='./icon/rewind.png';
			document.getElementById('playersizerimg').title="Collapse Player";
		}
	}
	
	</script>
	<meta name="author" content="Christian Breidohr">
	<meta name="keywords" content="MPD, remote control, mpd protocol handler, mdpc, media player daemon client remote">
	<meta name="date" content="2014-03-10T22:31:22+01:00">
	</head>
	<body id="body" style="<?php if(isset($_SESSION['bgcolor'])) { echo 'background-color:#'.$_SESSION['bgcolor'].';'; } ?>" onload="javascript:init();" onresize="javascript:setConstructSize();">
		<div id="header">
			<div id="logo" style="border:solid 0px;width:50px;float:left;margin:10px;">
					<a href="index.php"><img alt="" src="./icon/bc.black.jpg"  class="img" title="<?php echo $lang['refresh']; ?>" style="vertical-align:middle;border:0px;width:50px;height:50px;"></a>
			</div>
			<div id="logor" style="border:solid 0px;width:50px;float:right;margin:10px;">
				
			</div>
			<div id="player">
				<?php 
					$player = pctrl($fp,$status);
					echo '<div id="playerctrl">';
					echo $player['previous'].$player['play'].$player['pause'].$player['stop'].$player['next']; 
					echo $player['voldown'].$player['volmute'].$player['volup'];
					echo $player['repeat'].$player['random'].$player['single'].$player['consume'].$player['xfade'].$player['stepback'].$player['stepfor'];
					echo '</div>';
					echo '<div id="playersizer" style="width:25px;height:85px;top:0px;position:absolute;right:70px;"><img alt="" id="playersizerimg" class="img" src="./icon/rewind.png" width="25px;" height="85px" title="'.$lang['playercollapse'].'" onclick="javascript:collapseplayer();"></div>';
					echo '<div>'.$player['times'].'</div>';
					echo $player['title'].'<br>'.$player['artist'];				
					echo '	<div id="seekcoords"></div>
							<div id="seekbar" onmousemove="javascript:seekupdate(event,'.$player['duration'].');"  onclick="javascript:seeksend(event);" onmouseover="javascript:seekhelpon();" onmouseout="javascript:seekhelpoff();">
							<div id="seekelapsed" style="width:'.(($player['elapsed']/$player['duration'])*100).'%;"></div>						
							<input type="hidden" id="timeelapsed" value="'.$player['elapsed'].'">
							<input type="hidden" id="timemax" value="'.$player['duration'].'">
							<input type="hidden" id="playstatus" value="'.$status['state'].'">
							</div>';
				?>	
			</div>	
			<form method="get" action="index.php">
				<div id="shortcuts">
					<a href="index.php?do=showvz&arg1=/" name="treeroot"><img alt="" src="./icon/haus1.png" class="ctrlimg" title="<?php echo $lang['home']; ?>"></a>
					<a href="javascript:playlisten();"><img alt="" src="./icon/note.png" class="ctrlimg" title="<?php echo $lang['playlists']; ?>"></a>
					<a href="javascript:suche();"><img alt="" src="./icon/lupe.png" class="ctrlimg" title="<?php echo $lang['search']; ?>"></a>
					<input type="hidden" name="do" value="search">
					<input type="text" name="arg1" id="searchf" style="display:none;">
					<input type="submit" id="searchs" name="searchsend" value="<?php echo $lang['search']; ?>" style="display:none;">
				</div>
				<div id="cplhead">
					<table width="100%"><tr><td>
					<b><?php echo $lang['playlist']; ?></b>
					</td><td style="text-align:right;">
					<img alt="" src="./icon/play.png" class="img" id="playselected" style="height:20px;width:20px;" title="<?php echo $lang['playsel'];?>" onclick="javascript:cplselectsendplay();">
					<img alt="" src="./icon/up.png" class="img" id="upselected" style="height:20px;width:20px;" title="<?php echo $lang['moveselup'];?>" onclick="javascript:cplselectsendup();">
					<img alt="" src="./icon/down.png" class="img" id="downselected" style="height:20px;width:20px;" title="<?php echo $lang['moveseldown'];?>" onclick="javascript:cplselectsenddown();">
					<img alt="" src="./icon/del.png" class="img" id="delselected" style="height:20px;width:20px;" title="<?php echo $lang['delsel'];?>" onclick="javascript:cplselectsenddel();">
					<input type="text" id="playlistsavename" value="Playlist0001">
					<input type="button" value="<?php echo $lang['save'];?>" id="playlistsave" onclick="javascript:saveplaylistnow();"> <a href="javascript:playlistsave();"><img alt="" src="./icon/save.png" class="img" height="20" title="<?php echo $lang['save'];?>" style="margin-left:10px;"></a>
					<a href="javascript:clearplaylistnow();"><img alt="" src="./icon/neu.png" class="img" height="20" title="<?php echo $lang['newlist'];?>"></a>
					</td></tr></table>
				</div>
			</form>
		</div>
		<div id="content">
			<div id="cl">
				
				<a name="playlists"> </a>
				<?php lpl($fp); ?>  
				<a name="search"> </a> 
			</div>
			<div id="cm">
				
				<?php if($spl) show($fp,'pl',$arg); elseif($ssl) show($fp,'search',$sarg); else show($fp,'vz'); ?>
			</div>
			<div id="cr">
				<?php cpl($fp,$status); ?>
			</div>
		</div>
		<div id="footer">
			
			<?php stats($fp); ?>
			
			<img alt="" src="./icon/tool2.png" class="img" height="20px" width="26px" title="<?php echo $lang['settings']; ?>">
				Language: <?php langselect(); ?>
				Style: <?php themeselect(); ?>
				Background color: #<?php setbgcolor(); ?>
		</div>
	</body>
</html>
<?php 
doCommand($fp,'close');
fclose($fp); ?>
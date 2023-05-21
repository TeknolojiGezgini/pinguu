<?php
  session_start();
  $dbcon = mysqli_connect('localhost','webuser2','MEmzN5cLSXr7D0Gk','matcher');
  $time = strtotime(date('d.m.Y H:i:s'));
  $last_hour = $time-3600;
  $last_day = $time-86400;
  $users = mysqli_query($dbcon,"SELECT * FROM users");
  while($user = mysqli_fetch_array($users)){
    if(isset($user['gender']) AND isset($user['birth']) AND isset($user['looking_for_age_min']) AND isset($user['looking_for_age_max']) AND isset($user['looking_for_distance'])){
    $where = array();
    $having = array();
    
    if(isset($user['looking_for_distance']) AND $user['looking_for_distance']!=100 AND isset($user['geo_lat']) AND isset($user['geo_long'])){
      $distance_diff = $user['looking_for_distance']/111;
      $diff_1 = $user['geo_lat']+$distance_diff;
      $diff_2 = $user['geo_lat']-$distance_diff;
      $diff_3 = $user['geo_long']+$distance_diff;
      $diff_4 = $user['geo_long']-$distance_diff;
      array_push($where, 'geo_lat<'.$diff_1.' AND geo_lat>'.$diff_2.' AND geo_long<'.$diff_3.' AND geo_long>'.$diff_4);
      array_push($having,'km_diff>negative_distance AND km_diff<looking_for_distance');
    }else if($user['looking_for_distance']==100){
      array_push($having,'(km_diff>negative_distance AND km_diff<looking_for_distance) OR looking_for_distance=100');
    }
    
    if(isset($user['looking_for_age_min']) AND isset($user['looking_for_age_max'])){
      array_push($where, 'age>='.$user['looking_for_age_min'].' AND age<='.$user['looking_for_age_max']);
      array_push($where, 'looking_for_age_min<='.$user['age'].' AND looking_for_age_max>='.$user['age']);
    }
    
    if(isset($user['looking_for_gender'])){
      if($user['looking_for_gender']!=2){
        array_push($where,'gender='.$user['looking_for_gender']);
        array_push($where,'looking_for_gender IN('.$user['gender'].',2)');
      }else{
        array_push($where,'looking_for_gender IN('.$user['gender'].',2)');
      }
    }
    
    //Remove self from matches
    array_push($where,'id<>'.$_SESSION['user']['id']);
    //array_push($where,'fullname IS NOT NULL');
    array_push($where,'age IS NOT NULL');
    array_push($where,'geo_lat IS NOT NULL');
    array_push($where,'geo_long IS NOT NULL');
    array_push($where,'no_image=0');
    $where = ' WHERE '.implode(' AND ',$where);
    $having = ' HAVING '.implode(' AND ',$having);
    $ids = mysqli_query($dbcon,"SELECT id,IF(last_login>$last_day, '1','0') as online,looking_for_distance,(({$user['geo_lat']}-geo_lat)+({$user['geo_long']}-geo_long))*111 as km_diff,(-1*looking_for_distance) as negative_distance FROM users ".$where.$having." ORDER BY online DESC, popularity DESC,km_diff ASC");
    $matches = array();
    while($id = mysqli_fetch_array($ids)){
        $matches[] = '['.$id['id'].']';
    }
    preg_match_all('/(\[.*?\])/i',$user['liked'],$to_remove);
    preg_match_all('/(\[.*?\])/i',$user['passed'],$to_remove2);
    preg_match_all('/(\[.*?\])/i',$user['starsended'],$to_remove3);
    preg_match_all('/(\[.*?\])/i',$user['matched'],$to_remove4);
    //removing liked,passed,starsended from user_matches
    for($i=0;$i<count($to_remove2[1]);$i++){
      array_push($to_remove[1],$to_remove2[1][$i]);
    }
    for($i=0;$i<count($to_remove3[1]);$i++){
      array_push($to_remove[1],$to_remove3[1][$i]);
    }
    for($i=0;$i<count($to_remove4[1]);$i++){
      array_push($to_remove[1],$to_remove4[1][$i]);
    }
    $matches = array_diff($matches,$to_remove[1]);
    $matches = implode('',array_unique($matches));
    mysqli_query($dbcon,"UPDATE users SET matches='$matches' WHERE id={$user['id']}");
    }
  }
?>

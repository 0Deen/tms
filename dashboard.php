<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$pdo=db();
$scalar=function($sql,$p=[])use($pdo){return (int)(fetch_one($pdo,$sql,$p)['n']??0);};
$money=function($sql,$p=[])use($pdo){return (float)(fetch_one($pdo,$sql,$p)['n']??0);};
$today=date('Y-m-d'); $weekStart=date('Y-m-d',strtotime('monday this week')); $monthStart=date('Y-m-01');
$fleetTotal=$scalar('SELECT COUNT(*) n FROM vehicles');
$fleetActive=$scalar("SELECT COUNT(*) n FROM vehicles WHERE status='Active'");
$staffTotal=$scalar('SELECT COUNT(*) n FROM staff');
$driversActive=$scalar("SELECT COUNT(*) n FROM staff WHERE staff_role='Driver' AND status='Active'");
$tripsToday=$scalar('SELECT COUNT(*) n FROM trips WHERE trip_date=?',[$today]);
$passengersToday=$scalar('SELECT COALESCE(SUM(passengers_count),0) n FROM trips WHERE trip_date=?',[$today]);
$revenueToday=$money('SELECT COALESCE(SUM(total_revenue),0) n FROM trips WHERE trip_date=?',[$today]);
$revenueWeek=$money('SELECT COALESCE(SUM(total_revenue),0) n FROM trips WHERE trip_date BETWEEN ? AND ?',[$weekStart,$today]);
$openComplaints=$scalar("SELECT COUNT(*) n FROM complaints WHERE status<>'Resolved'");
$routesCount=$scalar('SELECT COUNT(*) n FROM routes');
$maintenance=$scalar("SELECT COUNT(*) n FROM vehicles WHERE status='Maintenance'");
$days=[];$revenues=[];$tripCounts=[];
for($i=6;$i>=0;$i--){$d=date('Y-m-d',strtotime("-$i days"));$days[]=date('D',strtotime($d));$revenues[]=$money('SELECT COALESCE(SUM(total_revenue),0) n FROM trips WHERE trip_date=?',[$d]);$tripCounts[]=$scalar('SELECT COUNT(*) n FROM trips WHERE trip_date=?',[$d]);}
$heatRows=fetch_all($pdo,'SELECT trip_date, HOUR(departure_time) AS trip_hour, COUNT(*) AS total FROM trips WHERE trip_date BETWEEN ? AND ? GROUP BY trip_date,HOUR(departure_time)',[date('Y-m-d',strtotime('-6 days')),$today]);
$heat=[];foreach($heatRows as $r)$heat[$r['trip_date'].'-'.$r['trip_hour']]=(int)$r['total'];
$recent=fetch_all($pdo,'SELECT t.*,v.registration_number,r.route_name,d.full_name driver_name FROM trips t JOIN vehicles v ON v.vehicle_id=t.vehicle_id JOIN routes r ON r.route_id=t.route_id JOIN staff d ON d.staff_id=t.driver_id ORDER BY t.trip_date DESC,t.departure_time DESC LIMIT 5');
$complaints=fetch_all($pdo,'SELECT c.*,p.full_name FROM complaints c JOIN passengers p ON p.passenger_id=c.passenger_id ORDER BY c.complaint_date DESC LIMIT 3');
$dayLabels=[];for($i=6;$i>=0;$i--){$d=date('Y-m-d',strtotime("-$i days"));$dayLabels[]=['date'=>$d,'label'=>date('d M',strtotime($d)),'short'=>date('D',strtotime($d))];}
$hours=[6,9,12,15,18];$maxHeat=1;foreach($heat as $v)$maxHeat=max($maxHeat,$v);
$readiness=$fleetTotal>0?(int)round(($fleetActive/$fleetTotal)*100):0;
page_header('Overview');
?>
<section class="dashboard-intro">
  <div><span class="intro-kicker"><i></i> YOUR OPERATIONS AT A GLANCE</span><h2>Good <?= (int)date('G')<12?'morning':((int)date('G')<17?'afternoon':'evening') ?>, <?= e(explode(' ',trim(current_user()['name']))[0]) ?>.</h2><p>Here’s what’s happening across your SACCO today.</p></div>
  <div class="intro-actions"><a class="btn btn-quiet" href="reports.php">View reports <span>↗</span></a><a class="btn btn-primary" href="trips.php"><span>＋</span> Record a trip</a></div>
</section>
<section class="dashboard-layout">
  <div class="dashboard-main-column">
    <div class="overview-metrics">
      <article class="overview-card metric-feature"><div class="metric-card-top"><span class="metric-icon dark-icon">▣</span><span class="metric-trend">FLEET OVERVIEW <i>↗</i></span></div><div class="metric-name">Total vehicles</div><div class="metric-number"><?= number_format($fleetTotal) ?></div><div class="metric-bottom"><span><b><?= number_format($fleetActive) ?></b> active and ready</span><span class="mini-progress"><i style="width:<?= $readiness ?>%"></i></span></div><div class="metric-art metric-art-orange"></div></article>
      <article class="overview-card"><div class="metric-card-top"><span class="metric-icon orange-icon">⇄</span><span class="metric-tag">TODAY</span></div><div class="metric-name">Trips completed</div><div class="metric-number"><?= number_format($tripsToday) ?></div><div class="metric-bottom"><span><?= number_format($passengersToday) ?> passengers carried</span><span class="metric-arrow">↗</span></div><div class="metric-spark spark-orange"><i style="height:28%"></i><i style="height:45%"></i><i style="height:38%"></i><i style="height:62%"></i><i style="height:50%"></i><i style="height:78%"></i><i style="height:100%"></i></div></article>
      <article class="overview-card"><div class="metric-card-top"><span class="metric-icon green-icon">KSh</span><span class="metric-tag">COLLECTED TODAY</span></div><div class="metric-name">Daily revenue</div><div class="metric-number money-number"><?= number_format($revenueToday,0) ?><small>KSh</small></div><div class="metric-bottom"><span>Weekly <b>KSh <?= number_format($revenueWeek,0) ?></b></span><span class="metric-arrow">↗</span></div><div class="metric-spark spark-green"><i style="height:36%"></i><i style="height:53%"></i><i style="height:42%"></i><i style="height:70%"></i><i style="height:58%"></i><i style="height:82%"></i><i style="height:100%"></i></div></article>
    </div>

    <section class="panel activity-panel">
      <div class="panel-heading"><div><span class="panel-overline">OPERATIONS PULSE</span><h3>Trip activity</h3><p>Daily dispatch pattern · last 7 days</p></div><span class="panel-select">Last 7 days <b>⌄</b></span></div>
      <div class="heatmap-wrap"><div class="heatmap-ylabels"><?php foreach(['6 AM','9 AM','12 PM','3 PM','6 PM'] as $label)echo '<span>'.e($label).'</span>'; ?></div><div class="heatmap-content"><div class="heatmap-grid" role="img" aria-label="Trip dispatch activity by day and departure hour"><?php foreach($dayLabels as $day): ?><div class="heatmap-day"><?php foreach($hours as $hour): $count=$heat[$day['date'].'-'.$hour]??0;$level=$count===0?0:min(4,(int)ceil(($count/$maxHeat)*4)); ?><span class="heat-cell heat-<?= $level ?>" title="<?= e($day['label']) ?>, <?= sprintf('%02d:00',$hour) ?> — <?= $count ?> trips"></span><?php endforeach; ?></div><?php endforeach; ?></div><div class="heatmap-xlabels"><?php foreach($dayLabels as $day)echo '<span>'.e($day['short']).'</span>'; ?></div></div></div>
      <div class="heatmap-legend"><span>Less activity</span><i class="heat-0"></i><i class="heat-1"></i><i class="heat-2"></i><i class="heat-3"></i><i class="heat-4"></i><span>More activity</span></div>
      <div class="activity-summary"><div><b><?= number_format(array_sum($tripCounts)) ?></b><span>Trips this week</span></div><div><b><?= number_format($passengersToday) ?></b><span>Passengers today</span></div><div><b><?= number_format($driversActive) ?></b><span>Active drivers</span></div></div>
    </section>

    <section class="panel timeline-panel">
      <div class="panel-heading"><div><span class="panel-overline">FLEET UTILISATION</span><h3>Revenue performance</h3><p>Collections across the last seven days</p></div><a class="text-link" href="reports.php">Full analytics ↗</a></div>
      <div class="chart-wrap revenue-chart"><canvas data-chart='<?= e(json_encode(['type'=>'line','labels'=>$days,'datasets'=>[['label'=>'Revenue (KSh)','data'=>$revenues,'borderColor'=>'#e85c19','backgroundColor'=>'rgba(232,92,25,.10)','fill'=>true]]])) ?>'></canvas></div>
    </section>

    <section class="panel recent-panel"><div class="panel-heading"><div><span class="panel-overline">ON THE ROAD</span><h3>Recent trips</h3><p>Latest journeys recorded in your system</p></div><a class="text-link" href="trips.php">All trips ↗</a></div><div class="table-wrap"><table><thead><tr><th>Trip ID</th><th>Vehicle</th><th>Route</th><th>Driver</th><th>Revenue</th></tr></thead><tbody>
    <?php if(!$recent): ?><tr><td colspan="5"><div class="empty-state"><b>No trips recorded yet</b>Record your first trip to see live operational activity.</div></td></tr><?php endif; ?>
    <?php foreach($recent as $t): ?><tr><td><b class="trip-id">#<?= (int)$t['trip_id'] ?></b><small class="table-sub"><?= e($t['trip_date']) ?></small></td><td><b><?= e($t['registration_number']) ?></b></td><td><?= e($t['route_name']) ?></td><td><?= e($t['driver_name']) ?></td><td><b>KSh <?= number_format((float)$t['total_revenue'],0) ?></b></td></tr><?php endforeach; ?>
    </tbody></table></div></section>
  </div>

  <aside class="dashboard-side-column">
    <section class="panel onboarding-panel"><div class="panel-heading"><div><span class="panel-overline">FLEET HEALTH</span><h3>Readiness check</h3></div><span class="readiness-score"><?= $readiness ?>%</span></div><div class="readiness-track"><span style="width:<?= $readiness ?>%"></span></div><div class="readiness-legend"><span><?= $fleetActive ?> ready</span><span><?= max(0,$fleetTotal-$fleetActive) ?> unavailable</span></div><div class="dark-task-card"><div class="dark-task-top"><div><span class="dark-task-kicker">DAILY CHECKLIST</span><h4>Keep the fleet moving.</h4></div><span class="task-count"><?= $maintenance ?> <small>in service</small></span></div><div class="task-list"><a href="vehicles.php"><span class="task-check <?= $fleetActive>0?'done':'' ?>"><?= $fleetActive>0?'✓':'·' ?></span><span><b>Active vehicles</b><small><?= $fleetActive ?> of <?= $fleetTotal ?> available for dispatch</small></span><span class="task-chevron">↗</span></a><a href="staff.php"><span class="task-check <?= $driversActive>0?'done':'' ?>"><?= $driversActive>0?'✓':'·' ?></span><span><b>Driver coverage</b><small><?= $driversActive ?> active drivers registered</small></span><span class="task-chevron">↗</span></a><a href="routes.php"><span class="task-check <?= $routesCount>0?'done':'' ?>"><?= $routesCount>0?'✓':'·' ?></span><span><b>Routes configured</b><small><?= $routesCount ?> routes available to assign</small></span><span class="task-chevron">↗</span></a><a href="complaints.php"><span class="task-check <?= $openComplaints===0?'done':'' ?>"><?= $openComplaints===0?'✓':'!' ?></span><span><b>Passenger service</b><small><?= $openComplaints ?> open complaints to review</small></span><span class="task-chevron">↗</span></a></div><a class="task-footer-link" href="vehicles.php">Open fleet management <span>→</span></a></div></section>

    <section class="panel active-fleet-panel"><div class="panel-heading"><div><span class="panel-overline">TEAM SNAPSHOT</span><h3>People & routes</h3></div><span class="soft-live"><i></i> LIVE</span></div><div class="side-stat"><span class="side-stat-icon">♙</span><span><small>Registered staff</small><b><?= number_format($staffTotal) ?></b></span><a href="staff.php">↗</a></div><div class="side-stat"><span class="side-stat-icon route-side-icon">⌁</span><span><small>Active routes</small><b><?= number_format($routesCount) ?></b></span><a href="routes.php">↗</a></div><div class="side-stat"><span class="side-stat-icon alert-side-icon">◇</span><span><small>Open complaints</small><b><?= number_format($openComplaints) ?></b></span><a href="complaints.php">↗</a></div><div class="side-panel-foot"><span>All figures update from your database.</span><span class="pulse-dot"></span></div></section>

    <section class="panel service-panel"><div class="panel-heading"><div><span class="panel-overline">PASSENGER EXPERIENCE</span><h3>Service desk</h3></div><a class="text-link" href="complaints.php">View all ↗</a></div><?php if(!$complaints): ?><div class="service-empty"><span>✦</span><b>All clear</b><small>No passenger complaints need attention.</small></div><?php endif; ?><?php foreach($complaints as $c): $cl=$c['status']==='Resolved'?'resolved':($c['status']==='In Progress'?'progress':'open'); ?><div class="complaint-row"><div class="complaint-avatar"><?= e(strtoupper(substr($c['full_name'],0,1))) ?></div><div class="complaint-copy"><b><?= e($c['full_name']) ?></b><small><?= e(mb_strimwidth($c['complaint_description'],0,48,'…')) ?></small></div><span class="pill <?= $cl ?>"><?= e($c['status']) ?></span></div><?php endforeach; ?></section>
  </aside>
</section>
<?php page_footer(); ?>

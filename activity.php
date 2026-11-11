<?php
require_once __DIR__.'/includes/bootstrap.php';require_role(['Administrator']);$pdo=db();
$rows=fetch_all($pdo,'SELECT a.*,u.full_name,u.username FROM audit_logs a LEFT JOIN users u ON u.user_id=a.user_id ORDER BY a.created_at DESC LIMIT 200');
page_header('Audit activity');
echo '<section class="card"><div class="card-head"><div><h2>System activity trail</h2><p>Latest 200 recorded events. Audit entries are read-only.</p></div><span class="pill active">Protected</span></div><div class="table-wrap"><table><thead><tr><th>Activity</th><th>Performed by</th><th>Record</th><th>IP address</th><th>Timestamp</th></tr></thead><tbody>';
if(!$rows)echo '<tr><td colspan="5"><div class="empty-state"><b>No audit events yet</b>Actions recorded by the application will appear here.</div></td></tr>';
foreach($rows as $r)echo '<tr><td><b>'.e($r['action']).'</b><br><small class="muted">'.e($r['description']??'').'</small></td><td>'.e($r['full_name']??'System').'<br><small class="muted">'.e($r['username']??'').'</small></td><td>'.e($r['entity_type']??'—').' '.e($r['entity_id']??'').'</td><td>'.e($r['ip_address']??'—').'</td><td>'.e($r['created_at']).'</td></tr>';
echo '</tbody></table></div></section>';page_footer();

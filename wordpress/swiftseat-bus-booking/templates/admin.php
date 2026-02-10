<div class="ssb-card">
  <h2>Admin Desk</h2>
  <form method="get" class="ssb-inline">
    <label>From <input type="date" name="from" value="<?php echo esc_attr($from); ?>"></label>
    <label>To <input type="date" name="to" value="<?php echo esc_attr($to); ?>"></label>
    <button type="submit">Filter reports</button>
  </form>
</div>

<div class="ssb-card">
  <h3>Add Bus</h3>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ssb-grid">
    <input type="hidden" name="action" value="ssb_admin_save"><input type="hidden" name="type" value="bus">
    <input name="bus_code" placeholder="Bus code" required>
    <input name="name" placeholder="Bus name" required>
    <input type="number" name="total_seats" value="40" min="1">
    <textarea name="notes" placeholder="Notes"></textarea>
    <button type="submit">Save bus</button>
  </form>
</div>

<div class="ssb-card">
  <h3>Add Schedule</h3>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ssb-grid">
    <input type="hidden" name="action" value="ssb_admin_save"><input type="hidden" name="type" value="schedule">
    <input type="date" name="schedule_date" required>
    <input type="time" name="schedule_time" required>
    <input name="pickup" placeholder="Pickup" required>
    <input name="dropoff" placeholder="Drop-off" required>
    <select name="bus_id"><?php foreach ($buses as $bus): ?><option value="<?php echo esc_attr($bus->id); ?>"><?php echo esc_html($bus->name); ?></option><?php endforeach; ?></select>
    <input type="number" name="passenger_unit" value="1" min="1">
    <select name="frequency"><option value="once">One-time</option><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select>
    <input type="number" name="iterations" value="1" min="1" max="31">
    <button type="submit">Create schedule(s)</button>
  </form>
</div>

<div class="ssb-card">
  <h3>Bookings Report</h3>
  <table>
    <thead><tr><th>ID</th><th>User</th><th>Schedule</th><th>Seats</th><th>Status</th><th>Move</th></tr></thead>
    <tbody>
      <?php foreach ($bookings as $bk): ?>
        <tr>
          <td><?php echo esc_html($bk->id); ?></td>
          <td><?php echo esc_html($bk->username); ?></td>
          <td><?php echo esc_html($bk->schedule_date . ' ' . $bk->schedule_time); ?></td>
          <td><?php echo esc_html($bk->seats); ?></td>
          <td><?php echo esc_html($bk->status); ?></td>
          <td>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ssb-inline">
              <input type="hidden" name="action" value="ssb_admin_save"><input type="hidden" name="type" value="booking_move">
              <input type="hidden" name="booking_id" value="<?php echo esc_attr($bk->id); ?>">
              <input type="number" name="new_schedule_id" placeholder="Schedule ID" required>
              <input name="admin_note" placeholder="Note">
              <button type="submit">Move</button>
            </form>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
              <input type="hidden" name="action" value="ssb_cancel_booking"><input type="hidden" name="booking_id" value="<?php echo esc_attr($bk->id); ?>">
              <button type="submit">Cancel</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="ssb-card">
  <h3>Bulk Delete</h3>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ssb-inline">
    <input type="hidden" name="action" value="ssb_admin_save"><input type="hidden" name="type" value="bulk_delete">
    <select name="entity"><option value="users">Users</option><option value="buses">Buses</option><option value="schedules">Schedules</option><option value="bookings">Bookings</option></select>
    <input name="ids" placeholder="Comma-separated IDs, e.g. 1,2,3" required>
    <button type="submit">Delete selected IDs</button>
  </form>
</div>

<div class="ssb-card">
  <h3>Monitor</h3>
  <p>Users: <?php echo esc_html(count($users)); ?> · Buses: <?php echo esc_html(count($buses)); ?> · Schedules: <?php echo esc_html(count($schedules)); ?> · Filtered bookings: <?php echo esc_html(count($bookings)); ?></p>
</div>

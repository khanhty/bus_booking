<div class="ssb-card">
  <h2>Book a Bus</h2>
  <form method="get">
    <label>Date <input type="date" name="date" value="<?php echo esc_attr($date); ?>"></label>
    <button type="submit">Filter</button>
  </form>
  <table>
    <thead><tr><th>Time</th><th>Route</th><th>Bus</th><th>Seats Left</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach ($schedules as $s): ?>
      <tr>
        <td><?php echo esc_html($s->schedule_time); ?></td>
        <td><?php echo esc_html($s->pickup . ' → ' . $s->dropoff); ?></td>
        <td><?php echo esc_html($s->bus_name); ?></td>
        <td><?php echo esc_html(max(0, (int)$s->total_seats - (int)$s->booked_seats)); ?></td>
        <td>
          <?php if ($s->status === 'available'): ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
              <input type="hidden" name="action" value="ssb_book">
              <input type="hidden" name="schedule_id" value="<?php echo esc_attr($s->id); ?>">
              <input type="number" name="seats" value="1" min="1" style="width:72px">
              <button type="submit">Book</button>
            </form>
          <?php else: ?>Unavailable<?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="ssb-card">
  <h3>My Bookings</h3>
  <table>
    <thead><tr><th>Date</th><th>Time</th><th>Route</th><th>Seats</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($bookings as $b): ?>
      <tr>
        <td><?php echo esc_html($b->schedule_date); ?></td>
        <td><?php echo esc_html($b->schedule_time); ?></td>
        <td><?php echo esc_html($b->pickup . ' → ' . $b->dropoff); ?></td>
        <td><?php echo esc_html($b->seats); ?></td>
        <td><?php echo esc_html($b->status); ?></td>
        <td>
          <?php if ($b->status === 'active'): ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
              <input type="hidden" name="action" value="ssb_cancel_booking">
              <input type="hidden" name="booking_id" value="<?php echo esc_attr($b->id); ?>">
              <button type="submit">Cancel</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

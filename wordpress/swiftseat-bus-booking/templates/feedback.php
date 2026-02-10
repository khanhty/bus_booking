<div class="ssb-card">
  <h2>Feedback</h2>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="ssb_feedback">
    <label>Rating <input type="number" name="rating" min="1" max="5" value="5"></label>
    <textarea name="message" rows="4" placeholder="Share your feedback..." required></textarea>
    <button type="submit">Submit</button>
  </form>
</div>

<div class="ssb-card">
  <h3>All Feedback</h3>
  <?php foreach ($feedback as $row): ?>
    <div class="ssb-item"><strong><?php echo esc_html($row->username); ?></strong> (<?php echo esc_html($row->rating); ?>/5): <?php echo esc_html($row->message); ?></div>
  <?php endforeach; ?>
</div>

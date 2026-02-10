<div class="ssb-card">
  <h2>Chat with Admin</h2>
  <div class="ssb-chat-window">
    <?php foreach ($messages as $m): ?>
      <div class="ssb-chat-msg"><strong><?php echo esc_html($m->username ?: $m->sender_role); ?>:</strong> <?php echo esc_html($m->message); ?></div>
    <?php endforeach; ?>
  </div>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="ssb_chat">
    <textarea name="message" rows="3" required placeholder="Type a message..."></textarea>
    <button type="submit">Send</button>
  </form>
</div>

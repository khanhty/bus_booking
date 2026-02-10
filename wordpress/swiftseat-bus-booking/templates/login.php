<div class="ssb-card">
  <h2>Login</h2>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="ssb_login">
    <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
    <input name="username" placeholder="Username" required>
    <input name="password" type="password" placeholder="Password" required>
    <button type="submit">Login</button>
  </form>
  <p><a href="<?php echo esc_url(site_url('/register')); ?>">Register</a> · <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Reset password</a></p>
</div>

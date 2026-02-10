<div class="ssb-card">
  <h2>Profile</h2>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="ssb_profile">
    <div class="ssb-grid">
      <input name="email" type="email" value="<?php echo esc_attr($user->email); ?>" required>
      <input name="first_name" value="<?php echo esc_attr($user->first_name); ?>" required>
      <input name="last_name" value="<?php echo esc_attr($user->last_name); ?>" required>
      <input name="phone" value="<?php echo esc_attr($user->phone); ?>">
      <input name="employee_id" value="<?php echo esc_attr($user->employee_id); ?>">
      <input name="department" value="<?php echo esc_attr($user->department); ?>">
      <input name="position" value="<?php echo esc_attr($user->position); ?>">
    </div>
    <button type="submit">Update profile</button>
  </form>
</div>

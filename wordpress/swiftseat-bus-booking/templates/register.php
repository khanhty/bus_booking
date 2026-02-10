<div class="ssb-card">
  <h2>Register</h2>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="ssb_register">
    <div class="ssb-grid">
      <input name="username" placeholder="Username" required>
      <input name="email" type="email" placeholder="Email" required>
      <input name="password" type="password" placeholder="Password" required>
      <input name="first_name" placeholder="First Name" required>
      <input name="last_name" placeholder="Last Name" required>
      <input name="phone" placeholder="Phone">
      <input name="employee_id" placeholder="Employee ID">
      <input name="department" placeholder="Department">
      <input name="position" placeholder="Position">
    </div>
    <button type="submit">Create Account</button>
  </form>
</div>

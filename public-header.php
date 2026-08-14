<?php
$current_public_page = basename(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : 'index.php');
function public_nav_current($page) {
    global $current_public_page;
    return $current_public_page === $page ? ' aria-current="page"' : '';
}
?>
<header class="site-header premium-public-header one-line-public-header" id="top">
  <div class="public-header-container">
  <a aria-label="Rustic Rose Productions home" class="brand" href="index.php#top">
    <span class="brand-logo"><img alt="" src="assets/images/logo.png"/></span>
    <span class="brand-copy"><strong>Rustic Rose Productions</strong><small>Wedding &amp; Event Planning</small></span>
  </a>

  <nav aria-label="Main navigation">
    <a href="index.php#top"<?php echo public_nav_current('index.php'); ?>>Home</a>
    <details class="nav-dropdown">
      <summary class="nav-dropdown-toggle" aria-expanded="false">
        <span>About</span><svg viewBox="0 0 12 8" aria-hidden="true"><path d="M1 1.5 6 6.5l5-5"/></svg>
      </summary>
      <div class="nav-dropdown-menu" role="menu">
        <a role="menuitem" href="our-story.php"<?php echo public_nav_current('our-story.php'); ?>><strong>Our Story</strong><small>The heart behind Rustic Rose</small></a>
        <a role="menuitem" href="meet-the-team.php"<?php echo public_nav_current('meet-the-team.php'); ?>><strong>Meet the Founder</strong><small>Personal planning, thoughtful support</small></a>
        <a role="menuitem" href="why-choose-us.php"<?php echo public_nav_current('why-choose-us.php'); ?>><strong>Why Choose Us</strong><small>Calm, detailed event coordination</small></a>
      </div>
    </details>
    <a href="services.php"<?php echo public_nav_current('services.php'); ?>>Services</a>
    <details class="nav-dropdown">
      <summary class="nav-dropdown-toggle" aria-expanded="false">
        <span>Explore Us</span><svg viewBox="0 0 12 8" aria-hidden="true"><path d="M1 1.5 6 6.5l5-5"/></svg>
      </summary>
      <div class="nav-dropdown-menu nav-dropdown-menu-compact" role="menu">
        <a role="menuitem" href="gallery.php"><strong>Photo Gallery</strong><small>Explore recent celebrations</small></a>
        <a role="menuitem" href="index.php#testimonials"><strong>Testimonials</strong><small>Kind words from clients</small></a>
      </div>
    </details>
    <div class="header-nav-actions" aria-label="Quick actions">
      <a class="nav-call nav-book" href="booking.php"<?php echo public_nav_current('booking.php'); ?>>Book a Consultation</a>
      <a class="nav-call" href="tel:+18303180819" aria-label="Call Rustic Rose Productions">Call Now</a>
    </div>
  </nav>

  <button aria-expanded="false" aria-label="Open menu" class="menu-toggle"><span></span><span></span><span></span></button>
  </div>
</header>

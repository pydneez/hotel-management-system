<!-- 
  This is the new footer, styled using classes from main.css.
  It's structured with a grid for the main content and a 
  separate bottom bar for the copyright.
-->
<footer class="footer-public">
    <div class="container footer-grid">

        <!-- Column 1: About -->
        <div class="footer-col">
            <h3 class="footer-logo">RoyalStay Hotel</h3>
            <p>Experience unforgettable stays and book your perfect room with us. Luxury and comfort await.</p>
            <!-- Staff Login Link - placed discreetly -->
            <a href="/login.php" class="footer-staff-login">Staff Login</a>
        </div>

        <!-- Column 2: Contact Info -->
        <div class="footer-col">
            <h4>Contact Info</h4>
            <ul class="footer-contact-list">
                <li>📞 +1 800 555 1234</li>
                <li>✉️ contact@royalstay.com</li>
                <li>📍 123 Luxury Ave, Bangkok, Thailand</li>
            </ul>
        </div>

        <!-- Column 3: Explore -->
        <div class="footer-col">
            <h4>Explore</h4>
            <ul class="footer-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="#rooms">Rooms</a></li>
                <li><a href="#">Guest Services</a></li>
                <li><a href="#">Contact Us</a></li>
            </ul>
        </div>

        <!-- Column 4: Subscribe -->
        <div class="footer-col">
            <h4>Subscribe Us</h4>
            <p>Subscribe for our latest updates and offers.</p>
            <form class="footer-subscribe-form" action="#" method="POST">
                <input type="email" name="email" placeholder="Enter Your Email" required>
                <button type="submit" class="btn-primary">Subscribe</button>
            </form>
        </div>

    </div>
    
    <!-- Bottom Copyright Bar -->
    <div class="footer-bottom-bar">
        <p>&copy; <?php echo date("Y"); ?> RoyalStay Hotel. All Rights Reserved.</p>
    </div>
</footer>


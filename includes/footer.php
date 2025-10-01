    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About Coastal Housing</h3>
                    <p>Your trusted partner for student accommodation in Kenya's coastal region. We connect students with quality, affordable housing options near major universities.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/properties.php">Properties</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/about.php">About Us</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/contact.php">Contact</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/terms.php">Terms of Service</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Mombasa Road, Nyali, Mombasa</p>
                    <p><i class="fas fa-phone"></i> +254 700 123 456</p>
                    <p><i class="fas fa-envelope"></i> info@coastalstudenthousing.co.ke</p>
                </div>
                
                 <div class="footer-section">
        <h3>Follow Us</h3>
        <div class="social-links">
            <a href="#" class="social-link facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="social-link twitter"><i class="fab fa-twitter"></i></a>
            <a href="#" class="social-link instagram"><i class="fab fa-instagram"></i></a>
            <a href="#" class="social-link linkedin"><i class="fab fa-linkedin-in"></i></a>
        </div>
        <p>Subscribe to our newsletter for updates</p>
        <form class="newsletter-form">
            <input type="email" placeholder="Enter your email" required>
            <button type="submit">Subscribe</button>
        </form>
    </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Kenya Coastal Student Housing. All rights reserved.</p>
            </div>
        </div>
        <style>
            /* Improved footer styling */
            .footer {
                background: var(--dark-color);
                color: white;
                padding: 3rem 0 1rem;
            }
            
            .footer-content {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 2rem;
                margin-bottom: 2rem;
            }
            
            .footer-section h3 {
                margin-bottom: 1rem;
                color: var(--accent-color);
                font-size: 1.2rem;
                font-weight: 600;
            }
            
            .footer-section p {
                margin-bottom: 0.5rem;
                line-height: 1.6;
                color: rgba(255, 255, 255, 0.9);
            }
            
            .footer-section ul {
                list-style: none;
                padding: 0;
            }
            
            .footer-section ul li {
                margin-bottom: 0.5rem;
            }
            
            .footer-section ul li a {
                color: rgba(255, 255, 255, 0.9);
                text-decoration: none;
                transition: color 0.3s ease;
                display: block;
                padding: 0.25rem 0;
            }
            
            .footer-section ul li a:hover {
                color: var(--accent-color);
                transform: translateX(5px);
            }

             .social-links {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .social-link i {
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .social-link.facebook {
            background: #3b5998;
            color: white;
        }

        .social-link.twitter {
            background: #1da1f2;
            color: white;
        }
        
        .social-link.instagram {
            background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
            color: white;
        }

        .social-link.linkedin {
            background: #0077b5;
            color: white;
        }
        
        .social-link:hover {
            transform: translateY(-5px) scale(1.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .social-link:hover i {
            transform: scale(1.2);
        }

        .social-link::before {
            content: attr(class);
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.75rem;
            opacity: 0;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .social-link:hover::before {
            bottom: -40px;
            opacity: 1;
        }
        
        .social-link.facebook::before {
            content: "Facebook";
        }
        
        .social-link.twitter::before {
            content: "Twitter";
        }

        .social-link.instagram::before {
            content: "Instagram";
        }
        
        .social-link.linkedin::before {
            content: "LinkedIn";
        }
           
            .newsletter-form {
                display: flex;
                gap: 0.5rem;
                margin-top: 1rem;
            }
            
            .newsletter-form input {
                flex: 1;
                padding: 0.75rem;
                border: none;
                border-radius: 4px;
                background: rgba(255, 255, 255, 0.1);
                color: white;
            }
            
            .newsletter-form input::placeholder {
                color: rgba(255, 255, 255, 0.7);
            }
            
            .newsletter-form button {
                padding: 0.75rem 1rem;
                background: var(--accent-color);
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                transition: background 0.3s ease;
            }
            
            .newsletter-form button:hover {
                background: #c0392b;
            }
            
            .footer-bottom {
                text-align: center;
                padding-top: 2rem;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                color: rgba(255, 255, 255, 0.7);
            }
            
            @media (max-width: 768px) {
                .footer-content {
                    grid-template-columns: 1fr;
                }
                
                .newsletter-form {
                    flex-direction: column;
                }
            }
        </style>
    </footer>
</body>
</html>
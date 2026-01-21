<?php

namespace Core\Mod\Web\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Core\Mod\Web\Models\Template;

class BioTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds 15 diverse biolink templates across multiple categories.
     */
    public function run(): void
    {
        if (! Schema::hasTable('biolink_templates')) {
            return;
        }

        // Skip if workspaces table doesn't exist (FK constraint would fail on SQLite)
        if (! Schema::hasTable('workspaces')) {
            return;
        }

        $templates = [
            // Business Templates
            [
                'name' => 'Professional Business',
                'category' => 'business',
                'description' => 'Clean, professional template for business consultants and corporate professionals',
                'blocks_json' => [
                    ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => null, 'alt' => '{{name}}']],
                    ['type' => 'heading', 'order' => 2, 'settings' => ['text' => '{{name}}', 'level' => 'h1']],
                    ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => '{{title}} at {{company}}']],
                    ['type' => 'link', 'order' => 4, 'settings' => ['url' => '{{website}}', 'text' => 'Visit Mod', 'icon' => 'globe']],
                    ['type' => 'link', 'order' => 5, 'settings' => ['url' => '{{linkedin}}', 'text' => 'Connect on LinkedIn', 'icon' => 'linkedin']],
                    ['type' => 'link', 'order' => 6, 'settings' => ['url' => 'mailto:{{email}}', 'text' => 'Email Me', 'icon' => 'mail']],
                    ['type' => 'vcard', 'order' => 7, 'settings' => ['name' => '{{name}}', 'email' => '{{email}}', 'phone' => '{{phone}}']],
                ],
                'settings_json' => [
                    'theme' => [
                        'background' => ['type' => 'color', 'color' => '#f8fafc'],
                        'text_color' => '#1e293b',
                        'button' => ['background_color' => '#0f172a', 'text_color' => '#ffffff', 'border_radius' => '8px'],
                        'font_family' => 'Inter',
                    ],
                    'seo' => ['title' => '{{name}} - {{title}}', 'description' => 'Connect with {{name}}, {{title}} at {{company}}'],
                ],
                'placeholders' => [
                    'name' => 'Your Name',
                    'title' => 'Job Title',
                    'company' => 'Company Name',
                    'website' => 'https://example.com',
                    'linkedin' => 'https://linkedin.com/in/username',
                    'email' => 'you@example.com',
                    'phone' => '+44 20 1234 5678',
                ],
                'is_system' => true,
                'is_premium' => false,
                'sort_order' => 10,
            ],

            // Creative Templates
            [
                'name' => 'Creative Portfolio',
                'category' => 'creative',
                'description' => 'Vibrant template for photographers, designers, and visual artists',
                'blocks_json' => [
                    ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => null, 'alt' => '{{name}}', 'shape' => 'circle']],
                    ['type' => 'heading', 'order' => 2, 'settings' => ['text' => '{{name}}', 'level' => 'h1']],
                    ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => '{{tagline}}']],
                    ['type' => 'image', 'order' => 4, 'settings' => ['url' => null, 'alt' => 'Portfolio showcase']],
                    ['type' => 'link', 'order' => 5, 'settings' => ['url' => '{{portfolio}}', 'text' => 'View Portfolio', 'icon' => 'image']],
                    ['type' => 'link', 'order' => 6, 'settings' => ['url' => '{{instagram}}', 'text' => 'Instagram', 'icon' => 'instagram']],
                    ['type' => 'link', 'order' => 7, 'settings' => ['url' => '{{behance}}', 'text' => 'Behance', 'icon' => 'briefcase']],
                    ['type' => 'socials', 'order' => 8, 'settings' => ['twitter' => '{{twitter}}', 'instagram' => '{{instagram}}', 'tiktok' => '{{tiktok}}']],
                ],
                'settings_json' => [
                    'theme' => [
                        'background' => ['type' => 'gradient', 'gradient_start' => '#667eea', 'gradient_end' => '#764ba2'],
                        'text_color' => '#ffffff',
                        'button' => ['background_color' => '#ffffff', 'text_color' => '#667eea', 'border_radius' => '24px'],
                        'font_family' => 'Poppins',
                    ],
                    'seo' => ['title' => '{{name}} - Creative Portfolio', 'description' => '{{tagline}}'],
                ],
                'placeholders' => [
                    'name' => 'Your Name',
                    'tagline' => 'Photographer & Visual Artist',
                    'portfolio' => 'https://portfolio.example.com',
                    'instagram' => 'https://instagram.com/username',
                    'behance' => 'https://behance.net/username',
                    'twitter' => 'https://twitter.com/username',
                    'tiktok' => 'https://tiktok.com/@username',
                ],
                'is_system' => true,
                'is_premium' => false,
                'sort_order' => 20,
            ],

            // Portfolio Template
            [
                'name' => 'Developer Portfolio',
                'category' => 'portfolio',
                'description' => 'Tech-focused template for software developers and engineers',
                'blocks_json' => [
                    ['type' => 'heading', 'order' => 1, 'settings' => ['text' => '{{name}}', 'level' => 'h1']],
                    ['type' => 'paragraph', 'order' => 2, 'settings' => ['text' => '{{role}} based in {{city}}']],
                    ['type' => 'divider', 'order' => 3, 'settings' => []],
                    ['type' => 'link', 'order' => 4, 'settings' => ['url' => '{{github}}', 'text' => 'GitHub Projects', 'icon' => 'github']],
                    ['type' => 'link', 'order' => 5, 'settings' => ['url' => '{{blog}}', 'text' => 'Technical Blog', 'icon' => 'book']],
                    ['type' => 'link', 'order' => 6, 'settings' => ['url' => '{{resume}}', 'text' => 'Download CV', 'icon' => 'download']],
                    ['type' => 'faq', 'order' => 7, 'settings' => ['items' => [['question' => 'Tech stack?', 'answer' => '{{stack}}']], 'title' => 'About Me']],
                ],
                'settings_json' => [
                    'theme' => [
                        'background' => ['type' => 'color', 'color' => '#0d1117'],
                        'text_color' => '#c9d1d9',
                        'button' => ['background_color' => '#238636', 'text_color' => '#ffffff', 'border_radius' => '6px'],
                        'font_family' => 'Source Sans 3',
                    ],
                    'seo' => ['title' => '{{name}} - {{role}}', 'description' => 'Portfolio of {{name}}, {{role}} based in {{city}}'],
                ],
                'placeholders' => [
                    'name' => 'Your Name',
                    'role' => 'Full Stack Developer',
                    'city' => 'London',
                    'github' => 'https://github.com/username',
                    'blog' => 'https://blog.example.com',
                    'resume' => 'https://example.com/cv.pdf',
                    'stack' => 'PHP, Laravel, JavaScript, React, PostgreSQL',
                ],
                'is_system' => true,
                'is_premium' => false,
                'sort_order' => 30,
            ],

            // Personal Template
            [
                'name' => 'Personal Brand',
                'category' => 'personal',
                'description' => 'Simple, elegant template for personal branding and networking',
                'blocks_json' => [
                    ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => null, 'alt' => '{{name}}']],
                    ['type' => 'heading', 'order' => 2, 'settings' => ['text' => 'Hi, I\'m {{name}}', 'level' => 'h1']],
                    ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => '{{bio}}']],
                    ['type' => 'link', 'order' => 4, 'settings' => ['url' => '{{website}}', 'text' => 'Personal Mod']],
                    ['type' => 'link', 'order' => 5, 'settings' => ['url' => '{{twitter}}', 'text' => 'Twitter']],
                    ['type' => 'link', 'order' => 6, 'settings' => ['url' => '{{youtube}}', 'text' => 'YouTube Channel']],
                ],
                'settings_json' => [
                    'theme' => [
                        'background' => ['type' => 'color', 'color' => '#fef3c7'],
                        'text_color' => '#78350f',
                        'button' => ['background_color' => '#f59e0b', 'text_color' => '#ffffff', 'border_radius' => '12px'],
                        'font_family' => 'Nunito',
                    ],
                    'seo' => ['title' => '{{name}} - Personal Links', 'description' => '{{bio}}'],
                ],
                'placeholders' => [
                    'name' => 'Your Name',
                    'bio' => 'Content creator, writer, and tech enthusiast.',
                    'website' => 'https://example.com',
                    'twitter' => 'https://twitter.com/username',
                    'youtube' => 'https://youtube.com/@username',
                ],
                'is_system' => true,
                'is_premium' => false,
                'sort_order' => 40,
            ],

            // Event Template
            [
                'name' => 'Event Landing',
                'category' => 'events',
                'description' => 'Template for conferences, meetups, and special events',
                'blocks_json' => [
                    ['type' => 'heading', 'order' => 1, 'settings' => ['text' => '{{event_name}}', 'level' => 'h1']],
                    ['type' => 'paragraph', 'order' => 2, 'settings' => ['text' => '{{date}} | {{location}}']],
                    ['type' => 'image', 'order' => 3, 'settings' => ['url' => null, 'alt' => 'Event banner']],
                    ['type' => 'paragraph', 'order' => 4, 'settings' => ['text' => '{{description}}']],
                    ['type' => 'link', 'order' => 5, 'settings' => ['url' => '{{tickets}}', 'text' => 'Get Tickets', 'icon' => 'ticket']],
                    ['type' => 'link', 'order' => 6, 'settings' => ['url' => '{{schedule}}', 'text' => 'View Schedule', 'icon' => 'calendar']],
                    ['type' => 'countdown', 'order' => 7, 'settings' => ['date' => '{{countdown_date}}', 'text' => 'Event starts in:']],
                ],
                'settings_json' => [
                    'theme' => [
                        'background' => ['type' => 'gradient', 'gradient_start' => '#ec4899', 'gradient_end' => '#8b5cf6'],
                        'text_color' => '#ffffff',
                        'button' => ['background_color' => '#ffffff', 'text_color' => '#ec4899', 'border_radius' => '8px'],
                        'font_family' => 'Montserrat',
                    ],
                    'seo' => ['title' => '{{event_name}}', 'description' => '{{description}}'],
                ],
                'placeholders' => [
                    'event_name' => 'Tech Conference 2026',
                    'date' => '15 March 2026',
                    'location' => 'London, UK',
                    'description' => 'Join us for a day of inspiring talks and networking.',
                    'tickets' => 'https://tickets.example.com',
                    'schedule' => 'https://schedule.example.com',
                    'countdown_date' => '2026-03-15T09:00:00',
                ],
                'is_system' => true,
                'is_premium' => false,
                'sort_order' => 50,
            ],

            // E-commerce Template
            [
                'name' => 'Shop Links',
                'category' => 'ecommerce',
                'description' => 'Perfect for small businesses and online shops',
                'blocks_json' => [
                    ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => null, 'alt' => '{{shop_name}}']],
                    ['type' => 'heading', 'order' => 2, 'settings' => ['text' => '{{shop_name}}', 'level' => 'h1']],
                    ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => '{{tagline}}']],
                    ['type' => 'product', 'order' => 4, 'settings' => ['name' => 'Featured Product', 'price' => '£29.99', 'url' => '{{product_1}}']],
                    ['type' => 'link', 'order' => 5, 'settings' => ['url' => '{{shop}}', 'text' => 'Shop All Products', 'icon' => 'shopping-cart']],
                    ['type' => 'link', 'order' => 6, 'settings' => ['url' => '{{instagram}}', 'text' => 'Follow on Instagram', 'icon' => 'instagram']],
                    ['type' => 'newsletter', 'order' => 7, 'settings' => ['title' => 'Get 10% off your first order', 'button_text' => 'Subscribe']],
                ],
                'settings_json' => [
                    'theme' => [
                        'background' => ['type' => 'color', 'color' => '#ffffff'],
                        'text_color' => '#1f2937',
                        'button' => ['background_color' => '#10b981', 'text_color' => '#ffffff', 'border_radius' => '8px'],
                        'font_family' => 'DM Sans',
                    ],
                    'seo' => ['title' => '{{shop_name}} - {{tagline}}', 'description' => 'Shop at {{shop_name}}. {{tagline}}'],
                ],
                'placeholders' => [
                    'shop_name' => 'Your Shop',
                    'tagline' => 'Handcrafted goods & unique gifts',
                    'shop' => 'https://shop.example.com',
                    'product_1' => 'https://shop.example.com/featured',
                    'instagram' => 'https://instagram.com/yourshop',
                ],
                'is_system' => true,
                'is_premium' => false,
                'sort_order' => 60,
            ],

            // Non-profit Template
            [
                'name' => 'Charity & Non-profit',
                'category' => 'nonprofit',
                'description' => 'Template for charities, foundations, and community organisations',
                'blocks_json' => [
                    ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => null, 'alt' => '{{org_name}}']],
                    ['type' => 'heading', 'order' => 2, 'settings' => ['text' => '{{org_name}}', 'level' => 'h1']],
                    ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => '{{mission}}']],
                    ['type' => 'link', 'order' => 4, 'settings' => ['url' => '{{donate}}', 'text' => 'Donate Now', 'icon' => 'heart']],
                    ['type' => 'link', 'order' => 5, 'settings' => ['url' => '{{volunteer}}', 'text' => 'Volunteer', 'icon' => 'users']],
                    ['type' => 'link', 'order' => 6, 'settings' => ['url' => '{{about}}', 'text' => 'Our Story', 'icon' => 'book-open']],
                    ['type' => 'socials', 'order' => 7, 'settings' => ['facebook' => '{{facebook}}', 'twitter' => '{{twitter}}', 'linkedin' => '{{linkedin}}']],
                ],
                'settings_json' => [
                    'theme' => [
                        'background' => ['type' => 'color', 'color' => '#f0fdf4'],
                        'text_color' => '#14532d',
                        'button' => ['background_color' => '#16a34a', 'text_color' => '#ffffff', 'border_radius' => '8px'],
                        'font_family' => 'Open Sans',
                    ],
                    'seo' => ['title' => '{{org_name}}', 'description' => '{{mission}}'],
                ],
                'placeholders' => [
                    'org_name' => 'Your Organisation',
                    'mission' => 'Making a difference in our community.',
                    'donate' => 'https://donate.example.org',
                    'volunteer' => 'https://volunteer.example.org',
                    'about' => 'https://example.org/about',
                    'facebook' => 'https://facebook.com/yourorg',
                    'twitter' => 'https://twitter.com/yourorg',
                    'linkedin' => 'https://linkedin.com/company/yourorg',
                ],
                'is_system' => true,
                'is_premium' => false,
                'sort_order' => 70,
            ],

            // Restaurant Template
            [
                'name' => 'Restaurant & Cafe',
                'category' => 'restaurant',
                'description' => 'Appetising template for restaurants, cafes, and food businesses',
                'blocks_json' => [
                    ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => null, 'alt' => '{{restaurant_name}}']],
                    ['type' => 'heading', 'order' => 2, 'settings' => ['text' => '{{restaurant_name}}', 'level' => 'h1']],
                    ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => '{{cuisine}} in {{location}}']],
                    ['type' => 'link', 'order' => 4, 'settings' => ['url' => '{{menu}}', 'text' => 'View Menu', 'icon' => 'book']],
                    ['type' => 'link', 'order' => 5, 'settings' => ['url' => '{{booking}}', 'text' => 'Book a Table', 'icon' => 'calendar']],
                    ['type' => 'link', 'order' => 6, 'settings' => ['url' => '{{delivery}}', 'text' => 'Order Delivery', 'icon' => 'truck']],
                    ['type' => 'paragraph', 'order' => 7, 'settings' => ['text' => 'Opening hours: {{hours}}']],
                    ['type' => 'map', 'order' => 8, 'settings' => ['address' => '{{address}}']],
                ],
                'settings_json' => [
                    'theme' => [
                        'background' => ['type' => 'color', 'color' => '#fef2f2'],
                        'text_color' => '#7f1d1d',
                        'button' => ['background_color' => '#dc2626', 'text_color' => '#ffffff', 'border_radius' => '8px'],
                        'font_family' => 'Playfair Display',
                    ],
                    'seo' => ['title' => '{{restaurant_name}} - {{cuisine}}', 'description' => '{{cuisine}} restaurant in {{location}}'],
                ],
                'placeholders' => [
                    'restaurant_name' => 'Your Restaurant',
                    'cuisine' => 'Modern British Cuisine',
                    'location' => 'London',
                    'menu' => 'https://menu.example.com',
                    'booking' => 'https://booking.example.com',
                    'delivery' => 'https://deliveroo.co.uk/restaurant',
                    'hours' => 'Mon-Sat 12:00-22:00',
                    'address' => '123 High Street, London, UK',
                ],
                'is_system' => true,
                'is_premium' => false,
                'sort_order' => 80,
            ],

            // Healthcare Template
            [
                'name' => 'Healthcare Professional',
                'category' => 'healthcare',
                'description' => 'Professional template for doctors, therapists, and health practitioners',
                'blocks_json' => [
                    ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => null, 'alt' => 'Dr. {{name}}']],
                    ['type' => 'heading', 'order' => 2, 'settings' => ['text' => 'Dr. {{name}}', 'level' => 'h1']],
                    ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => '{{speciality}}']],
                    ['type' => 'link', 'order' => 4, 'settings' => ['url' => '{{booking}}', 'text' => 'Book Appointment', 'icon' => 'calendar']],
                    ['type' => 'link', 'order' => 5, 'settings' => ['url' => '{{services}}', 'text' => 'Services Offered', 'icon' => 'clipboard']],
                    ['type' => 'link', 'order' => 6, 'settings' => ['url' => 'tel:{{phone}}', 'text' => 'Call Us', 'icon' => 'phone']],
                    ['type' => 'paragraph', 'order' => 7, 'settings' => ['text' => 'Clinic hours: {{hours}}']],
                ],
                'settings_json' => [
                    'theme' => [
                        'background' => ['type' => 'color', 'color' => '#eff6ff'],
                        'text_color' => '#1e3a8a',
                        'button' => ['background_color' => '#3b82f6', 'text_color' => '#ffffff', 'border_radius' => '8px'],
                        'font_family' => 'Lato',
                    ],
                    'seo' => ['title' => 'Dr. {{name}} - {{speciality}}', 'description' => '{{speciality}} - Book your appointment today'],
                ],
                'placeholders' => [
                    'name' => 'Your Name',
                    'speciality' => 'General Practitioner',
                    'booking' => 'https://booking.example.com',
                    'services' => 'https://example.com/services',
                    'phone' => '+44 20 1234 5678',
                    'hours' => 'Mon-Fri 9:00-17:00',
                ],
                'is_system' => true,
                'is_premium' => false,
                'sort_order' => 90,
            ],

            // Education Template
            [
                'name' => 'Teacher & Educator',
                'category' => 'education',
                'description' => 'Template for teachers, tutors, and educational content creators',
                'blocks_json' => [
                    ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => null, 'alt' => '{{name}}']],
                    ['type' => 'heading', 'order' => 2, 'settings' => ['text' => '{{name}}', 'level' => 'h1']],
                    ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => '{{subject}} Teacher']],
                    ['type' => 'link', 'order' => 4, 'settings' => ['url' => '{{classroom}}', 'text' => 'Virtual Classroom', 'icon' => 'monitor']],
                    ['type' => 'link', 'order' => 5, 'settings' => ['url' => '{{resources}}', 'text' => 'Study Resources', 'icon' => 'book-open']],
                    ['type' => 'link', 'order' => 6, 'settings' => ['url' => '{{schedule}}', 'text' => 'Class Schedule', 'icon' => 'calendar']],
                    ['type' => 'link', 'order' => 7, 'settings' => ['url' => 'mailto:{{email}}', 'text' => 'Contact Me', 'icon' => 'mail']],
                ],
                'settings_json' => [
                    'theme' => [
                        'background' => ['type' => 'color', 'color' => '#fef9c3'],
                        'text_color' => '#713f12',
                        'button' => ['background_color' => '#ca8a04', 'text_color' => '#ffffff', 'border_radius' => '8px'],
                        'font_family' => 'Merriweather',
                    ],
                    'seo' => ['title' => '{{name}} - {{subject}} Teacher', 'description' => 'Resources and links for {{subject}} students'],
                ],
                'placeholders' => [
                    'name' => 'Your Name',
                    'subject' => 'Mathematics',
                    'classroom' => 'https://classroom.example.com',
                    'resources' => 'https://resources.example.com',
                    'schedule' => 'https://schedule.example.com',
                    'email' => 'teacher@example.com',
                ],
                'is_system' => true,
                'is_premium' => false,
                'sort_order' => 100,
            ],

            // Premium Templates (require paid plan)
            [
                'name' => 'Luxury Brand',
                'category' => 'business',
                'description' => 'Premium template with elegant design for luxury brands',
                'blocks_json' => [
                    ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => null, 'alt' => '{{brand}}']],
                    ['type' => 'heading', 'order' => 2, 'settings' => ['text' => '{{brand}}', 'level' => 'h1']],
                    ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => '{{tagline}}']],
                    ['type' => 'image', 'order' => 4, 'settings' => ['url' => null, 'alt' => 'Brand showcase']],
                    ['type' => 'link', 'order' => 5, 'settings' => ['url' => '{{shop}}', 'text' => 'Explore Collection']],
                    ['type' => 'link', 'order' => 6, 'settings' => ['url' => '{{booking}}', 'text' => 'Book Consultation']],
                    ['type' => 'socials', 'order' => 7, 'settings' => ['instagram' => '{{instagram}}', 'pinterest' => '{{pinterest}}']],
                ],
                'settings_json' => [
                    'theme' => [
                        'background' => ['type' => 'color', 'color' => '#000000'],
                        'text_color' => '#d4af37',
                        'button' => ['background_color' => '#d4af37', 'text_color' => '#000000', 'border_radius' => '0px'],
                        'font_family' => 'Cormorant Garamond',
                    ],
                    'seo' => ['title' => '{{brand}} - {{tagline}}', 'description' => 'Discover {{brand}}: {{tagline}}'],
                ],
                'placeholders' => [
                    'brand' => 'Your Brand',
                    'tagline' => 'Timeless Elegance',
                    'shop' => 'https://shop.example.com',
                    'booking' => 'https://booking.example.com',
                    'instagram' => 'https://instagram.com/yourbrand',
                    'pinterest' => 'https://pinterest.com/yourbrand',
                ],
                'is_system' => true,
                'is_premium' => false,
                'sort_order' => 110,
            ],

            [
                'name' => 'Influencer Pro',
                'category' => 'creative',
                'description' => 'Advanced template for content creators and influencers',
                'blocks_json' => [
                    ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => null, 'alt' => '{{name}}', 'shape' => 'circle']],
                    ['type' => 'heading', 'order' => 2, 'settings' => ['text' => '{{name}}', 'level' => 'h1']],
                    ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => '{{bio}}']],
                    ['type' => 'youtube', 'order' => 4, 'settings' => ['url' => '{{youtube_video}}']],
                    ['type' => 'link', 'order' => 5, 'settings' => ['url' => '{{merch}}', 'text' => 'Shop Merchandise', 'icon' => 'shopping-bag']],
                    ['type' => 'link', 'order' => 6, 'settings' => ['url' => '{{patreon}}', 'text' => 'Support on Patreon', 'icon' => 'heart']],
                    ['type' => 'socials', 'order' => 7, 'settings' => ['youtube' => '{{youtube}}', 'instagram' => '{{instagram}}', 'tiktok' => '{{tiktok}}', 'twitter' => '{{twitter}}']],
                    ['type' => 'newsletter', 'order' => 8, 'settings' => ['title' => 'Join {{subscribers}} subscribers', 'button_text' => 'Subscribe']],
                ],
                'settings_json' => [
                    'theme' => [
                        'background' => ['type' => 'gradient', 'gradient_start' => '#ff6b6b', 'gradient_end' => '#feca57'],
                        'text_color' => '#ffffff',
                        'button' => ['background_color' => '#ffffff', 'text_color' => '#ff6b6b', 'border_radius' => '24px'],
                        'font_family' => 'Rubik',
                    ],
                    'seo' => ['title' => '{{name}} - Content Creator', 'description' => '{{bio}}'],
                ],
                'placeholders' => [
                    'name' => 'Your Name',
                    'bio' => 'Content creator sharing daily vlogs and lifestyle tips',
                    'youtube' => 'https://youtube.com/@username',
                    'youtube_video' => 'https://youtube.com/watch?v=example',
                    'instagram' => 'https://instagram.com/username',
                    'tiktok' => 'https://tiktok.com/@username',
                    'twitter' => 'https://twitter.com/username',
                    'merch' => 'https://merch.example.com',
                    'patreon' => 'https://patreon.com/username',
                    'subscribers' => '100K',
                ],
                'is_system' => true,
                'is_premium' => false,
                'sort_order' => 120,
            ],

            [
                'name' => 'Startup Launch',
                'category' => 'business',
                'description' => 'Modern template for tech startups and product launches',
                'blocks_json' => [
                    ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => null, 'alt' => '{{company}}']],
                    ['type' => 'heading', 'order' => 2, 'settings' => ['text' => '{{company}}', 'level' => 'h1']],
                    ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => '{{tagline}}']],
                    ['type' => 'video', 'order' => 4, 'settings' => ['url' => '{{demo_video}}']],
                    ['type' => 'link', 'order' => 5, 'settings' => ['url' => '{{signup}}', 'text' => 'Join Waitlist', 'icon' => 'zap']],
                    ['type' => 'link', 'order' => 6, 'settings' => ['url' => '{{pitch}}', 'text' => 'View Pitch Deck', 'icon' => 'presentation']],
                    ['type' => 'link', 'order' => 7, 'settings' => ['url' => '{{blog}}', 'text' => 'Product Updates', 'icon' => 'rss']],
                    ['type' => 'socials', 'order' => 8, 'settings' => ['twitter' => '{{twitter}}', 'linkedin' => '{{linkedin}}', 'github' => '{{github}}']],
                ],
                'settings_json' => [
                    'theme' => [
                        'background' => ['type' => 'gradient', 'gradient_start' => '#0f0f0f', 'gradient_end' => '#1a1a2e'],
                        'text_color' => '#ffffff',
                        'button' => ['background_color' => '#00ff87', 'text_color' => '#000000', 'border_radius' => '8px'],
                        'font_family' => 'Space Grotesk',
                    ],
                    'seo' => ['title' => '{{company}} - {{tagline}}', 'description' => '{{tagline}}'],
                ],
                'placeholders' => [
                    'company' => 'Your Startup',
                    'tagline' => 'Revolutionising the way you work',
                    'demo_video' => 'https://youtube.com/watch?v=demo',
                    'signup' => 'https://signup.example.com',
                    'pitch' => 'https://pitch.example.com',
                    'blog' => 'https://blog.example.com',
                    'twitter' => 'https://twitter.com/yourstartup',
                    'linkedin' => 'https://linkedin.com/company/yourstartup',
                    'github' => 'https://github.com/yourstartup',
                ],
                'is_system' => true,
                'is_premium' => false,
                'sort_order' => 130,
            ],

            [
                'name' => 'Music Artist',
                'category' => 'creative',
                'description' => 'Vibrant template for musicians and music producers',
                'blocks_json' => [
                    ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => null, 'alt' => '{{artist_name}}']],
                    ['type' => 'heading', 'order' => 2, 'settings' => ['text' => '{{artist_name}}', 'level' => 'h1']],
                    ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => '{{genre}} artist from {{location}}']],
                    ['type' => 'spotify', 'order' => 4, 'settings' => ['url' => '{{spotify_track}}']],
                    ['type' => 'link', 'order' => 5, 'settings' => ['url' => '{{spotify}}', 'text' => 'Listen on Spotify', 'icon' => 'music']],
                    ['type' => 'link', 'order' => 6, 'settings' => ['url' => '{{apple_music}}', 'text' => 'Apple Music', 'icon' => 'music']],
                    ['type' => 'link', 'order' => 7, 'settings' => ['url' => '{{merch}}', 'text' => 'Shop Merch', 'icon' => 'shopping-bag']],
                    ['type' => 'link', 'order' => 8, 'settings' => ['url' => '{{tour}}', 'text' => 'Tour Dates', 'icon' => 'calendar']],
                    ['type' => 'socials', 'order' => 9, 'settings' => ['instagram' => '{{instagram}}', 'youtube' => '{{youtube}}', 'tiktok' => '{{tiktok}}']],
                ],
                'settings_json' => [
                    'theme' => [
                        'background' => ['type' => 'gradient', 'gradient_start' => '#1db954', 'gradient_end' => '#191414'],
                        'text_color' => '#ffffff',
                        'button' => ['background_color' => '#1db954', 'text_color' => '#000000', 'border_radius' => '24px'],
                        'font_family' => 'Oswald',
                    ],
                    'seo' => ['title' => '{{artist_name}} - {{genre}} Artist', 'description' => 'Official links for {{artist_name}}'],
                ],
                'placeholders' => [
                    'artist_name' => 'Your Artist Name',
                    'genre' => 'Electronic',
                    'location' => 'London',
                    'spotify' => 'https://open.spotify.com/artist/yourid',
                    'spotify_track' => 'https://open.spotify.com/track/trackid',
                    'apple_music' => 'https://music.apple.com/artist/yourid',
                    'merch' => 'https://merch.example.com',
                    'tour' => 'https://tour.example.com',
                    'instagram' => 'https://instagram.com/artistname',
                    'youtube' => 'https://youtube.com/@artistname',
                    'tiktok' => 'https://tiktok.com/@artistname',
                ],
                'is_system' => true,
                'is_premium' => false,
                'sort_order' => 140,
            ],

            [
                'name' => 'Wedding & Events',
                'category' => 'events',
                'description' => 'Elegant template for weddings and special occasions',
                'blocks_json' => [
                    ['type' => 'heading', 'order' => 1, 'settings' => ['text' => '{{couple_names}}', 'level' => 'h1']],
                    ['type' => 'paragraph', 'order' => 2, 'settings' => ['text' => '{{date}}']],
                    ['type' => 'image', 'order' => 3, 'settings' => ['url' => null, 'alt' => 'Couple photo']],
                    ['type' => 'countdown', 'order' => 4, 'settings' => ['date' => '{{countdown_date}}', 'text' => 'Days until we say "I do"']],
                    ['type' => 'link', 'order' => 5, 'settings' => ['url' => '{{rsvp}}', 'text' => 'RSVP', 'icon' => 'calendar-check']],
                    ['type' => 'link', 'order' => 6, 'settings' => ['url' => '{{registry}}', 'text' => 'Gift Registry', 'icon' => 'gift']],
                    ['type' => 'link', 'order' => 7, 'settings' => ['url' => '{{venue}}', 'text' => 'Venue & Directions', 'icon' => 'map-pin']],
                    ['type' => 'link', 'order' => 8, 'settings' => ['url' => '{{photos}}', 'text' => 'Upload Your Photos', 'icon' => 'camera']],
                ],
                'settings_json' => [
                    'theme' => [
                        'background' => ['type' => 'gradient', 'gradient_start' => '#fce7f3', 'gradient_end' => '#fbcfe8'],
                        'text_color' => '#831843',
                        'button' => ['background_color' => '#db2777', 'text_color' => '#ffffff', 'border_radius' => '24px'],
                        'font_family' => 'Libre Baskerville',
                    ],
                    'seo' => ['title' => '{{couple_names}} Wedding', 'description' => 'Join us as we celebrate our special day on {{date}}'],
                ],
                'placeholders' => [
                    'couple_names' => 'Jane & John',
                    'date' => '20th June 2026',
                    'countdown_date' => '2026-06-20T14:00:00',
                    'rsvp' => 'https://rsvp.example.com',
                    'registry' => 'https://registry.example.com',
                    'venue' => 'https://maps.google.com/venue',
                    'photos' => 'https://photos.example.com',
                ],
                'is_system' => true,
                'is_premium' => false,
                'sort_order' => 150,
            ],
        ];

        foreach ($templates as $template) {
            // Auto-generate slug from name if not provided
            if (! isset($template['slug'])) {
                $template['slug'] = \Illuminate\Support\Str::slug($template['name']);
            }
            Template::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}

<?php
/**
 * ============================================================================
 * NEXLY — LANDING PAGE CONTENT & BRANDING CONFIGURATION
 * ============================================================================
 * HOW TO CUSTOMIZE:
 * - Edit $site_config below to change your brand name, tagline, CTA links,
 *   marquee announcement, and floating "Just shipped" pill text.
 * - Edit $benefits, $latest_shipped, $showcase_items, $testimonials,
 *   $pricing_plans, and $faq_items to update each section's cards.
 * - Replace placeholder SVG paths in /assets/img/ with your own screenshots.
 * ============================================================================
 */

$site_config = [
    // CHANGE BRANDING & HERO TEXT HERE:
    'name'               => 'Nexly',
    'badge'              => 'Nexly Studio 2.4 · Built for Creators & Students',
    'hero_headline'      => 'Motion-driven live chat for rooms that move fast.',
    'hero_subheadline'   => 'Drop voice waveforms, uncompressed snaps, and bilingual Khmer + English threads in a zero-latency studio built for human connection.',
    'cta_primary_text'   => 'Start chatting free',
    'cta_primary_url'    => !empty($current_user) ? (BASE_URL . '/chat.php') : (BASE_URL . '/register.php'),
    'cta_secondary_text' => 'Open Live Lobby',
    'cta_secondary_url'  => BASE_URL . '/chat.php',

    // 1. TOP MARQUEE BANNER ITEMS (Loops horizontally at top of page)
    'marquee_items' => [
        'NEW: 48kHz Studio Voice Waveforms are live now',
        'Sub-20ms SSE Real-Time Chat Engine',
        'Full Khmer & English Typography Support (សួស្តី)',
        'Zero-Compression Instant Photo Snaps',
        'Share Anywhere via Public Tunnel or Localhost',
    ],

    // 3. HERO "USED BY / FEATURED IN" LOGOS
    'featured_logos' => [
        'KROMA STUDIO',
        'PHNOM PENH TECH',
        'MINTLABS',
        'ANGKOR CREATIVE',
        'VERVE AUDIO',
        'HYPERLOOP KH',
    ],

    // 13. FLOATING BOTTOM PILL
    'floating_pill' => [
        'label' => 'Just shipped: Live Voice Notes 2.0 · yesterday',
        'url'   => BASE_URL . '/chat.php',
    ],
];

// 5. BENEFITS GRID (6 short cards: bold title + one sentence)
$benefits = [
    [
        'icon'  => '01',
        'title' => 'Sub-20ms Live Stream',
        'desc'  => 'Messages, typing indicators, and reactions arrive instantly over persistent Server-Sent Events without page refreshes.',
    ],
    [
        'icon'  => '02',
        'title' => '48kHz Voice Waveforms',
        'desc'  => 'Record, scrub, and play crystal-clear voice notes inline with interactive visual amplitude bars.',
    ],
    [
        'icon'  => '03',
        'title' => 'Uncompressed Photo Snaps',
        'desc'  => 'Share high-resolution work-in-progress shots, design mocks, or camera snaps directly inside any conversation.',
    ],
    [
        'icon'  => '04',
        'title' => 'Khmer & Latin Precision',
        'desc'  => 'Engineered with Inter and Noto Sans Khmer metrics so bilingual threads never clip vowel diacritics or subscripts.',
    ],
    [
        'icon'  => '05',
        'title' => 'Private & Global Rooms',
        'desc'  => 'Switch effortlessly between the community lounge and encrypted 1-on-1 direct threads with unread badge sync.',
    ],
    [
        'icon'  => '06',
        'title' => 'Tactile 60fps Motion',
        'desc'  => 'Every card, drawer, and reaction responds with physics-tuned GSAP curves that automatically respect reduced-motion preferences.',
    ],
];

// 6. "JUST SHIPPED / LATEST" GALLERY GRID (6 cards with local /assets/img/ placeholders)
$latest_shipped = [
    [
        'title' => 'Live Voice Notes 2.0',
        'tag'   => 'Audio Engine',
        'date'  => 'Yesterday',
        'image' => BASE_URL . '/assets/img/latest-voice-wave.svg',
        'alt'   => 'Interactive mint-green audio waveform player inside Nexly chat',
        'url'   => BASE_URL . '/chat.php',
    ],
    [
        'title' => 'Instant HD Snap Drop',
        'tag'   => 'Media Pipeline',
        'date'  => '3 days ago',
        'image' => BASE_URL . '/assets/img/latest-instant-snap.svg',
        'alt'   => 'Camera viewfinder and instant high-definition photo sharing card',
        'url'   => BASE_URL . '/chat.php',
    ],
    [
        'title' => 'Spatial Presence Rings',
        'tag'   => 'Live Lobby',
        'date'  => 'Last week',
        'image' => BASE_URL . '/assets/img/latest-studio-rooms.svg',
        'alt'   => 'Active studio member avatars with glowing mint online rings',
        'url'   => BASE_URL . '/chat.php',
    ],
    [
        'title' => '60fps Micro-Reactions',
        'tag'   => 'Interaction',
        'date'  => 'Last week',
        'image' => BASE_URL . '/assets/img/latest-reactions.svg',
        'alt'   => 'Floating reaction counter pill over a dark studio message bubble',
        'url'   => BASE_URL . '/chat.php',
    ],
    [
        'title' => 'Khmer + English Type Scale',
        'tag'   => 'Typography',
        'date'  => '2 weeks ago',
        'image' => BASE_URL . '/assets/img/latest-khmer-type.svg',
        'alt'   => 'Bilingual Khmer and English typography specimen in mint green',
        'url'   => BASE_URL . '/chat.php',
    ],
    [
        'title' => 'SSE Edge Hyper-Stream',
        'tag'   => 'Core Infra',
        'date'  => '2 weeks ago',
        'image' => BASE_URL . '/assets/img/latest-encryption.svg',
        'alt'   => 'Real-time 14ms round-trip latency graph for Nexly chat stream',
        'url'   => BASE_URL . '/chat.php',
    ],
];

// 7. SHOWCASE HORIZONTAL MARQUEE SCREENSHOTS (Local /assets/img/showcase-*.svg)
$showcase_items = [
    [
        'title'   => 'Global Studio Lobby',
        'caption' => 'Real-time community lounge with live presence',
        'image'   => BASE_URL . '/assets/img/showcase-lobby.svg',
        'alt'     => 'Screenshot of Nexly Global Studio Lobby interface',
    ],
    [
        'title'   => 'Direct 1-on-1 Threads',
        'caption' => 'Zero-latency private conversations & media',
        'image'   => BASE_URL . '/assets/img/showcase-dm.svg',
        'alt'     => 'Screenshot of Nexly Direct Messaging thread view',
    ],
    [
        'title'   => 'Studio Voice Recorder',
        'caption' => 'Interactive waveform scrubbing and playback',
        'image'   => BASE_URL . '/assets/img/showcase-voice.svg',
        'alt'     => 'Screenshot of Nexly Voice Recorder and audio waveform bubble',
    ],
    [
        'title'   => 'Creator Identity Cards',
        'caption' => 'Customizable bio, avatar, and verified badges',
        'image'   => BASE_URL . '/assets/img/showcase-profile.svg',
        'alt'     => 'Screenshot of Nexly Creator Profile page',
    ],
    [
        'title'   => '120Hz Mobile Experience',
        'caption' => 'Responsive touch navigation & instant drawer',
        'image'   => BASE_URL . '/assets/img/showcase-mobile.svg',
        'alt'     => 'Screenshot of Nexly responsive mobile chat layout',
    ],
];

// 8. TESTIMONIALS (3 quote cards with avatar, name, and role)
$testimonials = [
    [
        'quote'  => 'Nexly replaced three bloated apps for our design cohort. Dropping a voice note alongside a UI snap feels immediate, tactile, and genuinely fun.',
        'name'   => 'Sophea Chan',
        'role'   => 'Creative Director · Kroma Studio',
        'avatar' => BASE_URL . '/assets/img/avatar-sophea.svg',
    ],
    [
        'quote'  => 'The Khmer and English typography rendering is the cleanest I have ever seen in a web chat app. Zero lag even when 40 students are posting at once.',
        'name'   => 'Dara Vann',
        'role'   => 'Lead Frontend Engineer · Phnom Penh Tech',
        'avatar' => BASE_URL . '/assets/img/avatar-dara.svg',
    ],
    [
        'quote'  => 'It looks and moves like an award-winning portfolio site, yet underneath it is a rock-solid real-time messenger we use every single morning.',
        'name'   => 'Lina Kem',
        'role'   => 'Product Designer · MintLabs',
        'avatar' => BASE_URL . '/assets/img/avatar-lina.svg',
    ],
];

// 9. PRICING PLANS (Monthly / Yearly prices animate smoothly when toggled)
$pricing_plans = [
    [
        'name'          => 'Starter',
        'badge'         => 'For individuals',
        'popular'       => false,
        'price_monthly' => 0,
        'price_yearly'  => 0,
        'unit'          => 'forever free',
        'desc'          => 'Everything you need to jump into the Global Lobby and message friends in real time.',
        'cta_text'      => 'Join free now',
        'cta_url'       => !empty($current_user) ? (BASE_URL . '/chat.php') : (BASE_URL . '/register.php'),
        'features'      => [
            'Unlimited Global Lobby & 1-on-1 DMs',
            'Interactive voice notes up to 2 minutes',
            'Instant photo snaps (up to 5MB)',
            'Custom profile avatar, bio & location',
        ],
    ],
    [
        'name'          => 'Studio Pro',
        'badge'         => 'Most popular',
        'popular'       => true,
        'price_monthly' => 12,
        'price_yearly'  => 9,
        'unit'          => 'per member / mo',
        'desc'          => 'Built for creator collectives, student clubs, and design teams who live in motion.',
        'cta_text'      => 'Start Studio Pro',
        'cta_url'       => !empty($current_user) ? (BASE_URL . '/chat.php') : (BASE_URL . '/register.php'),
        'features'      => [
            'Everything in Starter, plus Verified Badge',
            'Uncompressed 48kHz Studio Voice Notes',
            'HD photo & artwork uploads up to 25MB',
            'Custom accent themes & priority SSE stream',
            'Pinned room announcements & moderation',
        ],
    ],
    [
        'name'          => 'Campus & Team',
        'badge'         => 'For organizations',
        'popular'       => false,
        'price_monthly' => 29,
        'price_yearly'  => 22,
        'unit'          => 'per workspace / mo',
        'desc'          => 'Dedicated self-hosted or cloud tunnel rooms with full admin governance and audit controls.',
        'cta_text'      => 'Launch Workspace',
        'cta_url'       => !empty($current_user) ? (BASE_URL . '/chat.php') : (BASE_URL . '/register.php'),
        'features'      => [
            'Unlimited members & private channels',
            'Full Admin Dashboard & user verification',
            'Custom domain & Cloudflare Tunnel scripts',
            'Dedicated MySQL backup & export tools',
        ],
    ],
];

// 10. FAQ ITEMS (Accessible accordion with smooth height animation)
$faq_items = [
    [
        'q' => 'How does Nexly achieve real-time chat without heavy WebSocket servers?',
        'a' => 'Nexly uses an optimized PHP + MySQL Server-Sent Events (SSE) streaming engine paired with lightweight delta polling fallbacks. Messages, voice notes, and photos appear in under 20ms on local and Cloudflare-tunneled connections.',
    ],
    [
        'q' => 'Can I share my local Nexly studio with friends on different Wi-Fi networks?',
        'a' => 'Yes! Nexly includes built-in support for Cloudflare Quick Tunnels. Any friend anywhere in the world can open your public HTTPS link on desktop or mobile and start chatting immediately.',
    ],
    [
        'q' => 'Does Nexly support both Khmer and English script natively?',
        'a' => 'Absolutely. Our typography stack pairs Inter with Noto Sans Khmer and calibrated line-height metrics so Khmer vowel signs and subscripts never clip in headings, chat bubbles, or buttons.',
    ],
    [
        'q' => 'How do voice notes and photo snaps work inside a conversation?',
        'a' => 'Click the microphone button in the chat composer to record audio directly from your browser with a live timer, or tap the photo icon to attach a snap. Both stream immediately into the thread.',
    ],
    [
        'q' => 'What happens if a user enables Reduced Motion on their device?',
        'a' => 'Nexly checks prefers-reduced-motion: reduce in both CSS and GSAP. Heavy scroll triggers, 3D card tilts, and Lenis momentum smoothing automatically step back so navigation stays instant and comfortable.',
    ],
    [
        'q' => 'Where do I customize the colors, text, and placeholder images?',
        'a' => 'All landing page copy, pricing tiers, and image paths live in includes/landing_data.php, while design tokens (--mint: #b3fca0, --near-black: #141314, --off-white: #f5f5f3) sit at the top of assets/css/landing.css.',
    ],
];

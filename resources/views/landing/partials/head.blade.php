<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@if(request()->routeIs('home'))
    <title>
        Sinar Bhakti Husada
    </title>
@else
    <title>
        {{ filled($title ?? null) ? $title.' | '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
    </title>
@endif



{{-- <link rel="icon" href="logo/favicon.ico" sizes="any"> --}}
{{-- <link rel="icon" href="logo/favicon.svg" type="image/svg+xml"> --}}
{{-- <link rel="icon" href="logo/logo-ysbh-v2-diamond.svg" type="image/svg+xml"> --}}
<link rel="icon" href="{{ asset('logo/emblem-sinar-bhakti-husada.svg') }}" type="image/svg+xml" >
{{-- <link rel="icon" href="logo/sunburst-seal-sinar-bhakti-husada.svg" type="image/svg+xml"> --}}
{{-- <link rel="apple-touch-icon" href="logo/apple-touch-icon.png"> --}}
{{-- <link rel="apple-touch-icon" href="logo/apple-touch-icon.png"> --}}

{{-- <style>
  :root{
    --forest:#064F3B;
    --forest-dark:#043328;
    --forest-tint:#0E6A4F;
    --gold:#EBCC26;
    --gold-soft:#F7EBAF;
    --coral:#E42326;
    --coral-dark:#BE1417;
    --paper:#FBF7EA;
    --paper-dim:#F3ECD6;
    --ink:#12241C;
    --ink-soft:#4B5D53;
    --mist:#E9F1EB;
    --white:#FFFFFF;
    --line: rgba(6,79,59,0.14);
    --shadow: 0 20px 50px -25px rgba(6,45,35,0.35);
    --radius-lg: 28px;
    --radius-md: 18px;
    --radius-sm: 10px;
  }

  *,*::before,*::after{ box-sizing:border-box; }
  html{ scroll-behavior:smooth; }
  @media (prefers-reduced-motion: reduce){
    html{ scroll-behavior:auto; }
    *{ animation-duration:0.001ms !important; animation-iteration-count:1 !important; transition-duration:0.001ms !important; }
  }

  body{
    margin:0;
    background:var(--paper);
    color:var(--ink);
    font-family:'Plus Jakarta Sans', sans-serif;
    -webkit-font-smoothing:antialiased;
    line-height:1.6;
    overflow-x:hidden;
  }

  h1,h2,h3,.display{
    font-family:'Fraunces', serif;
    color:var(--forest);
    margin:0;
    letter-spacing:-0.01em;
  }

  a{ color:inherit; text-decoration:none; }
  img{ max-width:100%; display:block; }

  .wrap{ max-width:1180px; margin:0 auto; padding:0 32px; }
  @media (max-width:640px){ .wrap{ padding:0 20px; } }

  .eyebrow{
    display:inline-flex; align-items:center; gap:10px;
    font-size:13px; font-weight:700; letter-spacing:0.16em; text-transform:uppercase;
    color:var(--coral-dark);
  }
  .eyebrow svg{ width:16px; height:16px; flex-shrink:0; }

  /* ===== Buttons ===== */
  .btn{
    display:inline-flex; align-items:center; justify-content:center; gap:8px;
    font-family:'Plus Jakarta Sans', sans-serif;
    font-weight:700; font-size:15.5px;
    padding:14px 28px; border-radius:999px; border:2px solid transparent;
    cursor:pointer; transition:transform .25s ease, box-shadow .25s ease, background-color .25s ease;
    white-space:nowrap;
  }
  .btn-coral{ background:var(--coral); color:var(--white); }
  .btn-coral:hover{ background:var(--coral-dark); transform:translateY(-2px); box-shadow:0 14px 28px -14px rgba(228,35,38,0.55); }
  .btn-ghost{ background:transparent; color:var(--forest); border-color:var(--forest); }
  .btn-ghost:hover{ background:var(--forest); color:var(--white); transform:translateY(-2px); }
  .btn-light{ background:var(--white); color:var(--forest); }
  .btn-light:hover{ transform:translateY(-2px); box-shadow:0 14px 28px -14px rgba(0,0,0,0.35); }
  .btn-outline-light{ background:transparent; color:var(--white); border-color:rgba(255,255,255,0.6); }
  .btn-outline-light:hover{ background:rgba(255,255,255,0.12); border-color:var(--white); }

  /* ===== Nav ===== */
  header{
    position:sticky; top:0; z-index:100;
    background:rgba(251,247,234,0.88);
    backdrop-filter:blur(10px);
    border-bottom:1px solid var(--line);
  }
  .nav{ display:flex; align-items:center; justify-content:space-between; padding:16px 32px; max-width:1180px; margin:0 auto; }
  .brand{ display:flex; align-items:center; gap:12px; }
  .brand svg{ width:42px; height:42px; }
  .brand-name{ font-family:'Fraunces', serif; font-weight:700; font-size:17px; color:var(--forest); line-height:1.15; }
  .brand-name span{ display:block; font-family:'Plus Jakarta Sans', sans-serif; font-weight:600; font-size:10.5px; letter-spacing:0.14em; color:var(--coral); text-transform:uppercase; margin-top:2px; }

  .nav-links{ display:flex; align-items:center; gap:36px; }
  .nav-links a{ font-weight:600; font-size:15px; color:var(--ink-soft); position:relative; padding:4px 0; }
  .nav-links a:hover{ color:var(--forest); }
  .nav-cta{ display:flex; align-items:center; gap:18px; }
  .burger{ display:none; background:none; border:none; cursor:pointer; padding:6px; }
  .burger span{ display:block; width:24px; height:2.5px; background:var(--forest); margin:5px 0; border-radius:2px; }

  @media (max-width:900px){
    .nav-links{
      position:fixed; inset:72px 0 0 0; background:var(--paper);
      flex-direction:column; justify-content:flex-start; align-items:flex-start;
      padding:32px; gap:24px; transform:translateX(100%); transition:transform .35s ease;
      border-top:1px solid var(--line);
    }
    .nav-links.open{ transform:translateX(0); }
    .nav-links a{ font-size:18px; }
    .burger{ display:block; }
    .nav-cta .btn-ghost{ display:none; }
  }

  section{ position:relative; }
  .section-pad{ padding:96px 0; }
  @media (max-width:640px){ .section-pad{ padding:64px 0; } }
  .mist-bg{ background:var(--mist); }

  .section-head{ max-width:640px; margin-bottom:56px; }
  .section-head h2{ font-size:clamp(1.9rem, 3.2vw, 2.6rem); font-weight:600; margin-top:14px; line-height:1.15; }
  .section-head p{ color:var(--ink-soft); font-size:16.5px; margin-top:16px; }

  /* ===== Breadcrumb ===== */
  .breadcrumb{ display:flex; align-items:center; gap:8px; font-size:13.5px; color:var(--ink-soft); margin-bottom:32px; flex-wrap:wrap; }
  .breadcrumb a{ font-weight:600; color:var(--ink-soft); }
  .breadcrumb a:hover{ color:var(--forest); }
  .breadcrumb span{ color:var(--ink-soft); opacity:0.5; }
  .breadcrumb .current{ color:var(--forest); font-weight:700; opacity:1; }

  /* ===== Program hero ===== */
  .program-hero-icon{
    width:72px; height:72px; border-radius:22px; background:#FBE6E6; color:var(--coral);
    display:flex; align-items:center; justify-content:center; margin-bottom:22px;
  }
  .program-hero-icon svg{ width:34px; height:34px; }
  .program-hero h1{ font-size:clamp(2rem, 4vw, 3rem); font-weight:600; line-height:1.12; margin:16px 0 20px; max-width:820px; }
  .program-hero p.lead{ font-size:18px; color:var(--ink-soft); max-width:720px; }

  /* ===== About-style two column (reused) ===== */
  .about-grid{ display:grid; grid-template-columns:0.9fr 1.1fr; gap:64px; align-items:start; }
  @media (max-width:860px){ .about-grid{ grid-template-columns:1fr; gap:36px; } }
  .about-grid .lead-text{ font-family:'Fraunces', serif; font-size:26px; font-weight:500; color:var(--forest); line-height:1.4; }
  .about-grid .lead-text em{ font-style:italic; color:var(--coral); }
  .about-copy p{ color:var(--ink-soft); margin-bottom:16px; }

  /* ===== Partner cards ===== */
  .partner-grid{ display:grid; grid-template-columns:repeat(2, 1fr); gap:28px; }
  @media (max-width:760px){ .partner-grid{ grid-template-columns:1fr; } }
  .partner-card{ background:var(--white); border-radius:var(--radius-lg); padding:36px 32px; border:1px solid var(--line); box-shadow:var(--shadow); }
  .partner-icon{ width:56px; height:56px; border-radius:16px; background:var(--gold-soft); color:var(--forest); display:flex; align-items:center; justify-content:center; margin-bottom:20px; }
  .partner-icon svg{ width:26px; height:26px; }
  .partner-card .tag{ font-size:12px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--coral-dark); margin-bottom:10px; }
  .partner-card h3{ font-size:20px; font-weight:600; margin-bottom:10px; }
  .partner-card p{ color:var(--ink-soft); font-size:15px; }

  /* ===== Component list (5 pillars) ===== */
  .component-list{ display:flex; flex-direction:column; gap:16px; }
  .component-card{
    display:flex; gap:24px; align-items:flex-start;
    background:var(--white); border:1px solid var(--line); border-radius:var(--radius-md);
    padding:26px 30px; box-shadow:var(--shadow);
  }
  .component-num{
    font-family:'Fraunces', serif; font-weight:700; font-size:15px; color:var(--forest);
    background:var(--gold-soft); width:40px; height:40px; border-radius:12px;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
  }
  .component-icon{ width:40px; height:40px; border-radius:12px; background:var(--mist); color:var(--forest); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .component-icon svg{ width:20px; height:20px; }
  .component-body h3{ font-size:17.5px; font-weight:600; margin-bottom:6px; }
  .component-body p{ color:var(--ink-soft); font-size:14.5px; margin:0; }

  /* ===== Region cards ===== */
  .region-grid{ display:grid; grid-template-columns:repeat(2, 1fr); gap:28px; }
  @media (max-width:760px){ .region-grid{ grid-template-columns:1fr; } }
  .region-card{ border-radius:var(--radius-lg); padding:32px; border:1px solid var(--line); background:var(--white); box-shadow:var(--shadow); }
  .region-tag{
    display:inline-flex; align-items:center; gap:8px; font-size:12.5px; font-weight:700;
    letter-spacing:0.08em; text-transform:uppercase; padding:6px 14px; border-radius:999px; margin-bottom:18px;
  }
  .region-high .region-tag{ background:#FBE6E6; color:var(--coral-dark); }
  .region-medium .region-tag{ background:var(--gold-soft); color:var(--forest); }
  .region-tag .dot{ width:7px; height:7px; border-radius:50%; }
  .region-high .dot{ background:var(--coral); }
  .region-medium .dot{ background:var(--gold); border:1px solid var(--forest); }
  .region-province{ font-size:13px; font-weight:700; color:var(--ink-soft); text-transform:uppercase; letter-spacing:0.06em; margin:18px 0 10px; }
  .region-province:first-of-type{ margin-top:4px; }
  .chip-row{ display:flex; flex-wrap:wrap; gap:8px; }
  .region-chip{
    background:var(--mist); color:var(--forest); font-weight:600; font-size:13.5px;
    padding:7px 14px; border-radius:999px;
  }
  .region-high .region-chip{ background:#FBE6E6; color:var(--coral-dark); }

  /* ===== Gallery placeholders ===== */
  .gallery-grid{ display:grid; grid-template-columns:repeat(4, 1fr); gap:18px; }
  @media (max-width:860px){ .gallery-grid{ grid-template-columns:repeat(2, 1fr); } }
  .gallery-placeholder{
    aspect-ratio:4/3; border-radius:var(--radius-md); border:2px dashed var(--line);
    background:var(--white); display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:10px; color:var(--ink-soft); text-align:center; padding:16px;
  }
  .gallery-placeholder svg{ width:28px; height:28px; color:var(--ink-soft); opacity:0.6; }
  .gallery-placeholder span{ font-size:12.5px; font-weight:600; }

  /* ===== Donation CTA (reused) ===== */
  .donate-cta{ background:var(--coral); color:var(--white); overflow:hidden; position:relative; }
  .donate-inner{ display:grid; grid-template-columns:1.2fr 0.8fr; gap:48px; align-items:center; position:relative; z-index:2; }
  @media (max-width:860px){ .donate-inner{ grid-template-columns:1fr; } }
  .donate-cta h2{ color:var(--white); font-size:clamp(1.9rem,3.4vw,2.5rem); font-weight:600; line-height:1.15; }
  .donate-cta p{ color:rgba(255,255,255,0.88); font-size:16.5px; margin:16px 0 28px; max-width:460px; }
  .donate-actions{ display:flex; gap:16px; flex-wrap:wrap; }
  .transfer-card{ background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.3); border-radius:var(--radius-md); padding:26px 28px; backdrop-filter:blur(4px); }
  .transfer-card .label{ font-size:12px; letter-spacing:0.1em; text-transform:uppercase; color:rgba(255,255,255,0.75); margin-bottom:8px; }
  .transfer-card .acct{ font-family:'Fraunces', serif; font-size:22px; font-weight:600; margin-bottom:4px; }
  .transfer-card .name{ font-size:14px; color:rgba(255,255,255,0.85); margin-bottom:16px; }
  .copy-btn{ background:var(--white); color:var(--coral); border:none; border-radius:999px; padding:9px 18px; font-weight:700; font-size:13.5px; cursor:pointer; }

  /* ===== Contact (reused) ===== */
  .contact-grid{ display:grid; grid-template-columns:1fr 1fr; gap:56px; }
  @media (max-width:860px){ .contact-grid{ grid-template-columns:1fr; gap:36px; } }
  .contact-item{ display:flex; gap:16px; margin-bottom:26px; }
  .contact-item .ico{ width:46px; height:46px; border-radius:14px; background:var(--white); border:1px solid var(--line); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .contact-item .ico svg{ width:21px; height:21px; color:var(--forest); }
  .contact-item .t{ font-weight:700; font-size:14.5px; color:var(--forest); margin-bottom:3px; }
  .contact-item .d{ font-size:14.5px; color:var(--ink-soft); }
  .map-frame{ border-radius:var(--radius-md); overflow:hidden; border:1px solid var(--line); height:100%; min-height:280px; background:var(--paper-dim); display:flex; align-items:center; justify-content:center; color:var(--ink-soft); font-size:14px; }

  footer{ background:var(--forest-dark); color:rgba(255,255,255,0.72); padding:64px 0 28px; }
  .footer-grid{ display:grid; grid-template-columns:1.4fr 1fr 1fr 1fr; gap:40px; margin-bottom:48px; }
  @media (max-width:760px){ .footer-grid{ grid-template-columns:1fr 1fr; } }
  .footer-brand{ display:flex; align-items:center; gap:10px; margin-bottom:14px; }
  .footer-brand svg{ width:36px; height:36px; }
  .footer-brand span{ font-family:'Fraunces', serif; font-weight:700; color:var(--white); font-size:16px; }
  footer p.desc{ font-size:14px; max-width:280px; }
  footer h4{ color:var(--white); font-size:13.5px; letter-spacing:0.08em; text-transform:uppercase; margin-bottom:18px; font-weight:700; }
  footer ul{ list-style:none; margin:0; padding:0; }
  footer li{ margin-bottom:12px; }
  footer a{ font-size:14.5px; }
  footer a:hover{ color:var(--gold); }
  .social-row{ display:flex; gap:10px; margin-top:6px; }
  .social-row a{ width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,0.08); display:flex; align-items:center; justify-content:center; }
  .social-row a:hover{ background:var(--coral); }
  .social-row svg{ width:16px; height:16px; color:var(--white); }
  .footer-bottom{ border-top:1px solid rgba(255,255,255,0.12); padding-top:24px; display:flex; justify-content:space-between; flex-wrap:wrap; gap:12px; font-size:13px; }

  .reveal{ opacity:0; transform:translateY(22px); transition:opacity .7s ease, transform .7s ease; }
  .reveal.in{ opacity:1; transform:translateY(0); }

  :focus-visible{ outline:3px solid var(--coral); outline-offset:3px; }
</style> --}}


@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles()
<script>
    console.log('✅ Vite Assets from HEAD Loaded Successfully!');
</script>

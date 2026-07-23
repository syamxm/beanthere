<?php
session_start();
require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/partials/icons.php';

$featured = [];
$result = $conn->query("SELECT id, name, description, image_path, price, category
                        FROM menu_items
                        WHERE category = 'menu' AND stock > 0
                        ORDER BY sort_order, name LIMIT 3");
while ($row = $result->fetch_assoc()) {
  $featured[] = $row;
}

$beans = [
  'assets/images/colombian-supremo.jpg',
  'assets/images/ethopian-yirgacheffe.jpg',
  'assets/images/vietnam-robusta.jpg',
];

$pageTitle = 'Bean There - Small-batch coffee';
?>
<!DOCTYPE html>
<?php include __DIR__ . '/../src/partials/html_open.php'; ?>

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
  <style>
    .grain {
      position: fixed;
      inset: 0;
      z-index: 30;
      pointer-events: none;
      opacity: 0.045;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    }

    .aurora {
      position: fixed;
      inset: 0;
      z-index: -1;
      pointer-events: none;
      background:
        radial-gradient(42rem 32rem at 80% 8%, rgb(var(--c-caramel) / 0.16), transparent 60%),
        radial-gradient(38rem 30rem at 6% 88%, rgb(var(--c-caramel) / 0.08), transparent 55%);
    }

    .reveal {
      opacity: 0;
      transform: translateY(2rem);
      filter: blur(6px);
      transition:
        opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1),
        transform 0.8s cubic-bezier(0.16, 1, 0.3, 1),
        filter 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .reveal.is-visible {
      opacity: 1;
      transform: none;
      filter: none;
    }

    .reveal[data-delay="1"] { transition-delay: 0.08s; }
    .reveal[data-delay="2"] { transition-delay: 0.16s; }
    .reveal[data-delay="3"] { transition-delay: 0.24s; }

    .marquee-track {
      display: inline-flex;
      white-space: nowrap;
      will-change: transform;
      animation: marquee 34s linear infinite;
    }

    @keyframes marquee {
      to { transform: translateX(-50%); }
    }

    html {
      scroll-behavior: smooth;
      scroll-padding-top: 5rem;
      scroll-snap-type: y proximity;
    }

    .snap-section {
      scroll-snap-align: start;
      scroll-margin-top: 5rem;
    }

    .section-nav {
      position: fixed;
      top: 50%;
      right: 1.5rem;
      transform: translateY(-50%);
      z-index: 40;
      flex-direction: column;
      gap: 1rem;
    }

    .section-nav__item {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: flex-end;
      width: 1.5rem;
      height: 1.5rem;
    }

    .section-nav__item::after {
      content: "";
      width: 0.5rem;
      height: 0.5rem;
      border-radius: 9999px;
      background: rgb(var(--c-crema) / 0.25);
      transition:
        width 0.5s cubic-bezier(0.16, 1, 0.3, 1),
        height 0.5s cubic-bezier(0.16, 1, 0.3, 1),
        background 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .section-nav__item:hover::after {
      background: rgb(var(--c-crema) / 0.6);
    }

    .section-nav__item.is-active::after {
      width: 0.75rem;
      height: 0.75rem;
      background: rgb(var(--c-caramel));
    }

    .section-nav__label {
      position: absolute;
      right: 2rem;
      white-space: nowrap;
      font-size: 10px;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: rgb(var(--c-crema));
      background: rgb(var(--c-roast) / 0.9);
      backdrop-filter: blur(8px);
      border: 1px solid rgb(var(--c-crema) / 0.1);
      padding: 0.35rem 0.7rem;
      border-radius: 9999px;
      opacity: 0;
      transform: translateX(0.5rem);
      pointer-events: none;
      transition:
        opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1),
        transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .section-nav__item:hover .section-nav__label,
    .section-nav__item:focus-visible .section-nav__label {
      opacity: 1;
      transform: translateX(0);
    }

    @media (prefers-reduced-motion: reduce) {
      html { scroll-behavior: auto; scroll-snap-type: none; }
      .reveal { opacity: 1; transform: none; filter: none; transition: none; }
      .marquee-track { animation: none; }
    }
  </style>
</head>

<body class="bg-espresso text-crema font-sans">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <div class="aurora" aria-hidden="true"></div>
  <div class="grain" aria-hidden="true"></div>

  <nav class="section-nav hidden md:flex" aria-label="Page sections">
    <a href="#hero" class="section-nav__item is-active" data-section-dot="hero" aria-label="Intro">
      <span class="section-nav__label">Intro</span>
    </a>
    <a href="#favourites" class="section-nav__item" data-section-dot="favourites" aria-label="Favourites">
      <span class="section-nav__label">Favourites</span>
    </a>
    <a href="#loyalty" class="section-nav__item" data-section-dot="loyalty" aria-label="Rewards">
      <span class="section-nav__label">Rewards</span>
    </a>
    <a href="#process" class="section-nav__item" data-section-dot="process" aria-label="How it works">
      <span class="section-nav__label">How it works</span>
    </a>
  </nav>

  <main id="main" class="relative overflow-hidden">

    <section id="hero" class="snap-section max-w-6xl mx-auto px-4 pt-16 pb-20 md:pt-28 md:pb-28">
      <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">

        <div class="reveal">
          <span class="inline-flex items-center gap-2 rounded-full bg-crema/5 ring-1 ring-crema/10 px-3 py-1 text-[10px] uppercase tracking-[0.25em] text-caramel font-medium">
            <span class="w-1.5 h-1.5 rounded-full bg-caramel"></span> Shah Alam · Since 2023
          </span>

          <h1 class="font-serif text-5xl sm:text-6xl lg:text-7xl font-light leading-[0.95] mt-6 mb-6">
            Coffee worth<br>the <span class="italic text-caramel">detour.</span>
          </h1>

          <p class="text-foam text-lg leading-relaxed mb-9 max-w-md">
            Twelve drinks, three single-origin beans, zero guesswork.
            Tell us how you like your coffee and we'll pick your cup.
          </p>

          <div class="flex flex-wrap gap-3">
            <a href="user_dashboard.php"
              class="group inline-flex items-center gap-3 bg-caramel text-espresso font-semibold pl-6 pr-2 py-2 rounded-full transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] hover:bg-crema active:scale-[0.98]">
              Browse the menu
              <span class="w-9 h-9 rounded-full bg-espresso/10 flex items-center justify-center transition-transform duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:scale-105">
                <?= bt_icon('arrow-up-right', 'w-4 h-4') ?>
              </span>
            </a>
            <a href="recommendation.php"
              class="group inline-flex items-center gap-3 border border-caramel/50 text-caramel pl-6 pr-2 py-2 rounded-full transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] hover:border-caramel hover:bg-caramel/10 active:scale-[0.98]">
              Recommend me a drink
              <span class="w-9 h-9 rounded-full bg-caramel/10 flex items-center justify-center transition-transform duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:scale-105">
                <?= bt_icon('sparkles', 'w-4 h-4') ?>
              </span>
            </a>
          </div>
        </div>

        <div class="reveal" data-delay="1">
          <div class="relative">
            <div class="p-2 rounded-[2.25rem] bg-crema/5 ring-1 ring-crema/10 shadow-warm-lg md:rotate-1">
              <div class="relative overflow-hidden rounded-[1.85rem] shadow-[inset_0_1px_0_rgb(var(--c-crema)/0.12)]">
                <img src="assets/images/thumbnail.jpg" alt="Coffee at Bean There"
                  class="object-cover w-full h-80 md:h-[30rem]">
                <div class="absolute inset-0 bg-gradient-to-t from-espresso/70 via-transparent to-transparent"></div>
              </div>
            </div>

            <div class="hidden md:flex absolute -top-5 -left-6 items-center gap-3 rounded-2xl bg-roast/90 backdrop-blur ring-1 ring-crema/10 px-4 py-3 shadow-warm -rotate-2">
              <span class="w-9 h-9 rounded-full bg-caramel/15 flex items-center justify-center text-caramel">
                <?= bt_icon('mug-hot', 'w-4 h-4') ?>
              </span>
              <div class="leading-tight">
                <p class="font-serif text-2xl">12</p>
                <p class="text-foam text-[11px] uppercase tracking-[0.15em]">drinks on tap</p>
              </div>
            </div>

            <div class="absolute -bottom-5 right-5 flex items-center gap-3 rounded-full bg-roast/90 backdrop-blur ring-1 ring-crema/10 pl-3 pr-5 py-2 shadow-warm">
              <div class="flex -space-x-3">
                <?php foreach ($beans as $bean): ?>
                  <img src="<?= htmlspecialchars($bean) ?>" alt=""
                    class="w-9 h-9 rounded-full object-cover ring-2 ring-roast">
                <?php endforeach; ?>
              </div>
              <p class="text-xs text-foam leading-tight">3 single-origin<br><span class="text-crema">beans</span></p>
            </div>
          </div>
        </div>

      </div>
    </section>

    <section class="border-y border-bean bg-roast/40 overflow-hidden">
      <div class="marquee-track py-4 text-foam">
        <?php for ($m = 0; $m < 2; $m++): ?>
          <span class="flex items-center text-sm uppercase tracking-[0.25em]" aria-hidden="<?= $m === 1 ? 'true' : 'false' ?>">
            <?php foreach (['Single-origin', 'Small-batch', 'Roasted weekly', 'Order ahead', 'Skip the queue'] as $word): ?>
              <span class="px-6">·&nbsp;&nbsp;<?= $word ?></span>
            <?php endforeach; ?>
          </span>
        <?php endfor; ?>
      </div>
    </section>

    <section id="favourites" class="snap-section max-w-6xl mx-auto px-4 py-24">
      <div class="reveal flex items-end justify-between gap-6 mb-12">
        <div>
          <span class="text-caramel text-[10px] uppercase tracking-[0.25em] font-medium">The regulars</span>
          <h2 class="font-serif text-4xl md:text-5xl font-light mt-3">House favourites</h2>
          <p class="text-foam mt-3">What regulars keep coming back for.</p>
        </div>
        <a href="user_dashboard.php" class="hidden sm:inline-flex items-center gap-2 text-caramel hover:text-crema transition-colors duration-500 whitespace-nowrap">
          Full menu <?= bt_icon('arrow-up-right', 'w-3.5 h-3.5') ?>
        </a>
      </div>

      <div class="grid lg:grid-cols-3 lg:grid-rows-2 gap-5">
        <?php foreach ($featured as $i => $item): ?>
          <?php $isFeature = $i === 0; ?>
          <form action="customize.php" method="post"
            class="reveal group <?= $isFeature ? 'lg:col-span-2 lg:row-span-2' : 'lg:col-start-3' ?>"
            data-delay="<?= $i ?>">
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
            <input type="hidden" name="from_section" value="<?= htmlspecialchars($item['category']) ?>">
            <div class="h-full p-1.5 rounded-[2rem] bg-crema/5 ring-1 ring-crema/10 transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] hover:ring-caramel/40 hover:shadow-warm">
              <div class="h-full flex flex-col overflow-hidden rounded-[1.6rem] bg-roast">
                <div class="relative overflow-hidden <?= $isFeature ? 'flex-1 min-h-[15rem]' : 'h-44' ?>">
                  <img loading="lazy" src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"
                    class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:scale-105">
                  <span class="absolute top-3 left-3 rounded-full bg-espresso/70 backdrop-blur text-caramel text-xs font-semibold px-3 py-1 tabular-nums">
                    RM<?= number_format($item['price'], 2) ?>
                  </span>
                </div>
                <div class="p-5 <?= $isFeature ? 'md:p-7' : '' ?>">
                  <h3 class="font-serif <?= $isFeature ? 'text-2xl md:text-3xl' : 'text-xl' ?> font-light mb-2"><?= htmlspecialchars($item['name']) ?></h3>
                  <p class="text-foam text-sm leading-relaxed mb-5 <?= $isFeature ? 'max-w-md' : '' ?>"><?= htmlspecialchars($item['description']) ?></p>
                  <button type="submit"
                    class="inline-flex items-center gap-2 bg-caramel/10 text-caramel font-semibold pl-5 pr-2 py-2 rounded-full transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:bg-caramel group-hover:text-espresso active:scale-[0.98]">
                    Order this
                    <span class="w-7 h-7 rounded-full bg-espresso/10 flex items-center justify-center">
                      <?= bt_icon('arrow-up-right', 'w-3.5 h-3.5') ?>
                    </span>
                  </button>
                </div>
              </div>
            </div>
          </form>
        <?php endforeach; ?>
      </div>
    </section>

    <section id="loyalty" class="snap-section max-w-6xl mx-auto px-4 py-12 md:py-20">
      <div class="reveal p-2 rounded-[2.5rem] bg-crema/5 ring-1 ring-crema/10 shadow-warm-lg">
        <div class="rounded-[2rem] bg-roast p-8 md:p-14 grid md:grid-cols-2 gap-10 items-center shadow-[inset_0_1px_0_rgb(var(--c-crema)/0.08)]">
          <div>
            <span class="text-caramel text-[10px] uppercase tracking-[0.25em] font-medium">Loyalty</span>
            <h2 class="font-serif text-3xl md:text-4xl font-light mt-3 mb-4 leading-tight">Every ringgit<br>brews the next cup.</h2>
            <p class="text-foam leading-relaxed mb-8 max-w-md">
              Earn 1 point per RM1 spent, swap points for discount vouchers,
              and level up from bronze to silver to gold to earn faster.
            </p>
            <?php if (isset($_SESSION['current_user'])): ?>
              <a href="rewards.php"
                class="group inline-flex items-center gap-3 bg-caramel text-espresso font-semibold pl-6 pr-2 py-2 rounded-full transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] hover:bg-crema active:scale-[0.98]">
                See my rewards
                <span class="w-9 h-9 rounded-full bg-espresso/10 flex items-center justify-center transition-transform duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:translate-x-0.5 group-hover:-translate-y-0.5">
                  <?= bt_icon('arrow-up-right', 'w-4 h-4') ?>
                </span>
              </a>
            <?php else: ?>
              <div class="flex flex-wrap gap-3">
                <a href="user_register.php"
                  class="group inline-flex items-center gap-3 bg-caramel text-espresso font-semibold pl-6 pr-2 py-2 rounded-full transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] hover:bg-crema active:scale-[0.98]">
                  Join free &amp; start earning
                  <span class="w-9 h-9 rounded-full bg-espresso/10 flex items-center justify-center transition-transform duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:translate-x-0.5 group-hover:-translate-y-0.5">
                    <?= bt_icon('arrow-up-right', 'w-4 h-4') ?>
                  </span>
                </a>
                <a href="rewards.php"
                  class="inline-flex items-center px-6 py-2 rounded-full border border-caramel/50 text-caramel hover:bg-caramel/10 transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)]">
                  How it works
                </a>
              </div>
            <?php endif; ?>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <?php
            $tiers = [
              ['label' => 'Bronze', 'note' => '1× points', 'tone' => 'text-caramel'],
              ['label' => 'Silver', 'note' => '1.25× · 500 pts', 'tone' => 'text-foam'],
              ['label' => 'Gold', 'note' => '1.5× · 1500 pts', 'tone' => 'text-crema'],
            ];
            foreach ($tiers as $t): ?>
              <div class="p-1 rounded-[1.4rem] bg-crema/5 ring-1 ring-crema/10">
                <div class="rounded-[1.1rem] bg-espresso px-3 py-6 text-center">
                  <span class="<?= $t['tone'] ?> flex justify-center mb-3"><?= bt_icon('medal', 'w-7 h-7') ?></span>
                  <p class="font-semibold text-sm"><?= $t['label'] ?></p>
                  <p class="text-foam text-[11px] mt-1 leading-tight"><?= $t['note'] ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

    <section id="process" class="snap-section max-w-6xl mx-auto px-4 py-24">
      <div class="reveal mb-14">
        <span class="text-caramel text-[10px] uppercase tracking-[0.25em] font-medium">How it works</span>
        <h2 class="font-serif text-4xl md:text-5xl font-light mt-3">From bean to cup<br>in three taps.</h2>
      </div>

      <div class="grid md:grid-cols-3 gap-10 md:gap-12">
        <?php
        $steps = [
          ['icon' => 'mug-hot', 'title' => 'Browse the menu', 'body' => 'Twelve drinks and three single-origin beans, described honestly and priced fairly.'],
          ['icon' => 'sparkles', 'title' => 'Get a recommendation', 'body' => "Tell us roast, caffeine and flavour, and we'll match a drink from our actual menu."],
          ['icon' => 'bag', 'title' => 'Order your way', 'body' => 'Customise sugar, milk and toppings, then pick up in store or get it delivered.'],
        ];
        foreach ($steps as $i => $step): ?>
          <div class="reveal group" data-delay="<?= $i ?>">
            <div class="flex items-baseline gap-4 mb-5">
              <span class="font-serif text-5xl font-light text-caramel/30 group-hover:text-caramel/60 transition-colors duration-700 tabular-nums">0<?= $i + 1 ?></span>
              <span class="h-px flex-1 bg-bean"></span>
              <span class="text-caramel"><?= bt_icon($step['icon'], 'w-5 h-5') ?></span>
            </div>
            <h3 class="font-serif text-2xl font-light mb-3"><?= $step['title'] ?></h3>
            <p class="text-foam text-sm leading-relaxed"><?= $step['body'] ?></p>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (!isset($_SESSION['current_user'])): ?>
        <div class="reveal text-center mt-20">
          <a href="user_register.php"
            class="group inline-flex items-center gap-3 bg-caramel text-espresso font-semibold pl-8 pr-2 py-3 rounded-full transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] hover:bg-crema active:scale-[0.98]">
            Create a free account
            <span class="w-10 h-10 rounded-full bg-espresso/10 flex items-center justify-center transition-transform duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:scale-105">
              <?= bt_icon('arrow-up-right', 'w-4 h-4') ?>
            </span>
          </a>
          <p class="text-foam text-sm mt-4">Members earn vouchers on every order.</p>
        </div>
      <?php endif; ?>
    </section>

  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>

  <script>
    (function () {
      var els = document.querySelectorAll('.reveal');
      if (!('IntersectionObserver' in window)) {
        els.forEach(function (el) { el.classList.add('is-visible'); });
        return;
      }
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            io.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
      els.forEach(function (el) { io.observe(el); });
    })();

    (function () {
      var dots = document.querySelectorAll('[data-section-dot]');
      if (!dots.length || !('IntersectionObserver' in window)) return;

      var byId = {};
      dots.forEach(function (dot) { byId[dot.getAttribute('data-section-dot')] = dot; });

      var setActive = function (id) {
        dots.forEach(function (dot) {
          dot.classList.toggle('is-active', dot.getAttribute('data-section-dot') === id);
        });
      };

      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting && byId[entry.target.id]) setActive(entry.target.id);
        });
      }, { threshold: 0.5, rootMargin: '-40% 0px -40% 0px' });

      document.querySelectorAll('.snap-section').forEach(function (section) { io.observe(section); });
    })();
  </script>
</body>

</html>

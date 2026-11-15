document.documentElement.classList.add('js');

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');

const menuToggle = document.querySelector('.menu-toggle');
const menu = document.querySelector('#site-navigation');

if (menuToggle && menu) {
  const setExpanded = (expanded) => {
    menuToggle.setAttribute('aria-expanded', String(expanded));
    menuToggle.setAttribute(
      'aria-label',
      expanded ? 'Zamknij menu nawigacji' : 'Otwórz menu nawigacji',
    );
  };

  const closeMenu = () => {
    menu.classList.remove('menu--open');
    setExpanded(false);
  };

  menuToggle.addEventListener('click', () => {
    const isOpen = menu.classList.toggle('menu--open');
    setExpanded(isOpen);
  });

  menu.addEventListener('click', (event) => {
    if (event.target instanceof Element && event.target.closest('a')) {
      closeMenu();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && menu.classList.contains('menu--open')) {
      closeMenu();
      menuToggle.focus();
    }
  });
}

const countPrice = (cell) => {
  if (cell.dataset.counted === 'true') {
    return;
  }

  cell.dataset.counted = 'true';

  const original = cell.textContent;
  const match = original.match(/\d+/);

  if (!match) {
    return;
  }

  const target = Number(match[0]);
  const prefix = original.slice(0, match.index);
  const suffix = original.slice(match.index + match[0].length);
  const duration = 900;
  const start = performance.now();

  const frame = (now) => {
    const progress = Math.min((now - start) / duration, 1);
    const eased = 1 - (1 - progress) ** 3;

    cell.textContent = prefix + Math.round(target * eased) + suffix;

    if (progress < 1) {
      requestAnimationFrame(frame);
    } else {
      cell.textContent = original;
    }
  };

  requestAnimationFrame(frame);
};

const countPricesIn = (scope) => {
  scope.querySelectorAll('td.num').forEach(countPrice);
};

const initScrollReveal = () => {
  const items = [];

  document.querySelectorAll('.cards > li').forEach((tile, index) => {
    const card = tile.querySelector('.card');

    if (!card) {
      return;
    }

    card.style.setProperty('--i', String(index));
    card.classList.add('reveal');
    items.push(card);
  });

  document.querySelectorAll('.price-table tbody tr').forEach((row, index) => {
    row.style.setProperty('--i', String(index));
    row.classList.add('reveal');
    items.push(row);
  });

  const footerBlocks = document.querySelectorAll(
    '.site-footer .footer-heading, .site-footer .contact-grid, .site-footer .footer-bottom',
  );

  footerBlocks.forEach((block, index) => {
    block.style.setProperty('--i', String(index));
    block.classList.add('reveal');
    items.push(block);
  });

  if (!items.length) {
    return;
  }

  const showAll = ({ count }) => {
    items.forEach((item) => {
      item.classList.add('is-visible');

      if (count) {
        countPricesIn(item);
      }
    });
  };

  if (reducedMotion.matches) {
    showAll({ count: false });
    return;
  }

  if (!('IntersectionObserver' in window)) {
    showAll({ count: true });
    return;
  }

  const observer = new IntersectionObserver(
    (entries, current) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }

        if (entry.target.closest('.site-footer')) {
          footerBlocks.forEach((block) => {
            block.classList.add('is-visible');
            current.unobserve(block);
          });
          return;
        }

        entry.target.classList.add('is-visible');
        countPricesIn(entry.target);
        current.unobserve(entry.target);
      });
    },
    { rootMargin: '0px 0px -10% 0px', threshold: 0.2 },
  );

  items.forEach((item) => observer.observe(item));
};

const initPricingReveal = () => {
  const items = Array.from(document.querySelectorAll('.pricing__intro > *'));

  if (!items.length) {
    return;
  }

  items.forEach((item, index) => {
    item.style.setProperty('--i', String(index));
    item.classList.add('reveal');
  });

  const showAll = () => {
    items.forEach((item) => item.classList.add('is-visible'));
  };

  if (reducedMotion.matches) {
    showAll();
    return;
  }

  if (!('IntersectionObserver' in window)) {
    showAll();
    return;
  }

  const observer = new IntersectionObserver(
    (entries, current) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }

        entry.target.classList.add('is-visible');
        current.unobserve(entry.target);
      });
    },
    { rootMargin: '0px 0px -50% 0px', threshold: 0 },
  );

  items.forEach((item) => observer.observe(item));
};

const initCardTilt = () => {
  if (reducedMotion.matches || !finePointer.matches) {
    return;
  }

  const maxTilt = 3;

  document.querySelectorAll('.cards .card').forEach((card) => {
    let frame = 0;

    card.addEventListener('pointermove', (event) => {
      if (frame) {
        return;
      }

      const { clientX, clientY } = event;

      frame = requestAnimationFrame(() => {
        frame = 0;

        const rect = card.getBoundingClientRect();
        const x = (clientX - rect.left) / rect.width - 0.5;
        const y = (clientY - rect.top) / rect.height - 0.5;

        card.style.setProperty('--ry', `${(x * maxTilt * 2).toFixed(2)}deg`);
        card.style.setProperty('--rx', `${(-y * maxTilt * 2).toFixed(2)}deg`);
      });
    });

    card.addEventListener('pointerleave', () => {
      if (frame) {
        cancelAnimationFrame(frame);
        frame = 0;
      }

      card.style.setProperty('--rx', '0deg');
      card.style.setProperty('--ry', '0deg');
    });
  });
};

initScrollReveal();
initPricingReveal();
initCardTilt();

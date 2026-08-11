(function(){
  const toggle = document.querySelector('.bg-mobile-toggle');
  const panel  = document.querySelector('.bg-mobile-panel');

  if(toggle && panel){
    toggle.addEventListener('click', function(){
      const isOpen = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!isOpen));
      panel.hidden = isOpen;
      document.body.classList.toggle('bg-menu-open', !isOpen);
    });
  }
})();

(function(){
  const header = document.querySelector('.bg-header');
  const hero = document.querySelector('.bg-hero, .bg-page-hero');
  if(!header) return;

  function updateHeaderTone(){
    const headerHeight = header.offsetHeight || 0;
    const heroBottom = hero ? hero.getBoundingClientRect().bottom : 0;
    const heroTop = hero ? hero.getBoundingClientRect().top : 0;
    const overDarkHero = Boolean(hero && heroTop < headerHeight && heroBottom > headerHeight * .68);
    header.classList.toggle('is-over-dark', overDarkHero);
    header.classList.toggle('is-over-light', !overDarkHero);
  }

  updateHeaderTone();
  window.addEventListener('scroll', updateHeaderTone, { passive:true });
  window.addEventListener('resize', updateHeaderTone);
  window.addEventListener('load', updateHeaderTone);
})();

(function(){
  const galleries = document.querySelectorAll('[data-bg-living-gallery]');
  if(!galleries.length) return;

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  galleries.forEach(function(gallery){
    const items = Array.from(gallery.querySelectorAll('.bg-home-gallery-photo'));
    if(!items.length) return;

    function showWindow(startIndex){
      items.forEach(function(item, index){
        const relativeIndex = (index - startIndex + items.length) % items.length;
        const isVisible = relativeIndex < 5;
        item.classList.toggle('is-visible', isVisible);
        if(isVisible){
          item.setAttribute('data-bg-gallery-slot', String(relativeIndex + 1));
        }
      });
    }

    let index = 0;
    showWindow(index);

    if(prefersReducedMotion || items.length <= 5) return;

    window.setInterval(function(){
      index = (index + 1) % items.length;
      showWindow(index);
    }, 4200);
  });
})();

(function(){
  const carousels = document.querySelectorAll('.bg-home-gallery-carousel[data-bg-coverflow="true"]');
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  carousels.forEach(function(carousel){
    const track = carousel.querySelector('.wp-block-gallery, .bg-gallery-fallback-grid');
    if(!track) return;

    const images = Array.from(track.querySelectorAll('img'));
    if(!images.length) return;

    Array.from(track.children).forEach(function(child){
      if(!child.querySelector('img')){
        child.classList.add('bg-gallery-empty');
      }
    });

    const slides = images.map(function(image){
      const slide = image.closest('figure, .wp-block-image') || image.parentElement;
      slide.classList.add('bg-gallery-slide');
      slide.setAttribute('tabindex', '0');
      return {
        node: slide,
        image: image,
        src: image.currentSrc || image.src,
        alt: image.alt || '',
        caption: (slide.querySelector('figcaption') || {}).textContent || image.alt || ''
      };
    });

    if(!slides.length) return;
    track.classList.add('bg-gallery-coverflow');

    const viewer = document.createElement('div');
    viewer.className = 'bg-gallery-viewer';
    viewer.hidden = true;
    viewer.innerHTML = '<button type="button" class="bg-gallery-viewer-close" aria-label="Close gallery image">&times;</button><figure><img alt=""><figcaption></figcaption></figure>';
    document.body.appendChild(viewer);

    const viewerImage = viewer.querySelector('img');
    const viewerCaption = viewer.querySelector('figcaption');
    const viewerClose = viewer.querySelector('.bg-gallery-viewer-close');

    let index = 0;
    let timer = null;

    function slideOffset(slideIndex){
      let offset = slideIndex - index;
      const half = slides.length / 2;
      if(offset > half) offset -= slides.length;
      if(offset < -half) offset += slides.length;
      return Math.max(-3, Math.min(3, offset));
    }

    function moveTo(nextIndex, smooth){
      index = (nextIndex + slides.length) % slides.length;
      slides.forEach(function(slide, slideIndex){
        const offset = slideOffset(slideIndex);
        const absoluteOffset = Math.abs(offset);
        const isActive = slideIndex === index;
        slide.node.classList.toggle('is-active', isActive);
        slide.node.classList.toggle('is-hidden', absoluteOffset >= 3 && slides.length > 5);
        slide.node.setAttribute('aria-current', isActive ? 'true' : 'false');
        slide.node.style.setProperty('--bg-gallery-x', (offset * 42) + '%');
        slide.node.style.setProperty('--bg-gallery-scale', String(Math.max(.62, 1 - absoluteOffset * .12)));
        slide.node.style.setProperty('--bg-gallery-rotate', (offset * -6) + 'deg');
        slide.node.style.setProperty('--bg-gallery-opacity', String(Math.max(.18, 1 - absoluteOffset * .22)));
        slide.node.style.setProperty('--bg-gallery-z', String(10 - absoluteOffset));
      });
      track.classList.toggle('is-changing', Boolean(smooth && !prefersReducedMotion));
    }

    function start(){
      if(prefersReducedMotion || timer) return;
      timer = window.setInterval(function(){
        moveTo(index + 1, true);
      }, 4800);
    }

    function stop(){
      if(!timer) return;
      window.clearInterval(timer);
      timer = null;
    }

    function openViewer(slide){
      viewerImage.src = slide.src;
      viewerImage.alt = slide.alt;
      viewerCaption.textContent = slide.caption;
      viewerCaption.hidden = !slide.caption;
      viewer.hidden = false;
      document.body.classList.add('bg-gallery-viewer-open');
    }

    function closeViewer(){
      viewer.hidden = true;
      document.body.classList.remove('bg-gallery-viewer-open');
    }

    const section = carousel.closest('.bg-home-gallery') || document;
    const previous = section.querySelector('[data-bg-gallery-prev]');
    const next = section.querySelector('[data-bg-gallery-next]');

    if(previous){
      previous.addEventListener('click', function(){
        stop();
        moveTo(index - 1, true);
        start();
      });
    }

    if(next){
      next.addEventListener('click', function(){
        stop();
        moveTo(index + 1, true);
        start();
      });
    }

    slides.forEach(function(slide, slideIndex){
      slide.node.addEventListener('click', function(){
        stop();
        if(slideIndex === index){
          openViewer(slide);
        }else{
          moveTo(slideIndex, true);
        }
        start();
      });
      slide.node.addEventListener('mouseenter', function(){
        stop();
        moveTo(slideIndex, true);
      });
      slide.node.addEventListener('keydown', function(event){
        if(event.key === 'Enter' || event.key === ' '){
          event.preventDefault();
          slide.node.click();
        }
      });
    });

    viewerClose.addEventListener('click', closeViewer);
    viewer.addEventListener('click', function(event){
      if(event.target === viewer) closeViewer();
    });
    document.addEventListener('keydown', function(event){
      if(event.key === 'Escape' && !viewer.hidden) closeViewer();
    });

    carousel.addEventListener('mouseenter', stop);
    carousel.addEventListener('mouseleave', start);
    carousel.addEventListener('focusin', stop);
    carousel.addEventListener('focusout', start);

    moveTo(0, false);
    start();
  });
})();

(function(){
  const viewports = document.querySelectorAll('[data-bg-gallery-fit]');
  if(!viewports.length) return;

  let resizePinged = false;

  function clampWideChildren(viewport){
    const limit = viewport.clientWidth;
    if(!limit) return;

    viewport.querySelectorAll('[style], iframe, video, canvas, img').forEach(function(node){
      if(node === viewport) return;
      const width = node.scrollWidth || node.offsetWidth || 0;
      if(width > limit + 2){
        node.style.maxWidth = '100%';
        if(node.tagName !== 'IMG'){
          node.style.width = '100%';
          node.style.minWidth = '0';
        }
      }
    });
  }

  function fitGallery(viewport){
    const frame = viewport.querySelector('.bg-home-gallery-plugin-frame');
    if(!frame) return;

    frame.classList.remove('is-scaled');
    frame.style.transform = '';
    frame.style.width = '100%';
    frame.style.height = 'auto';
    viewport.style.height = '';

    clampWideChildren(viewport);

    const available = viewport.clientWidth;
    const needed = frame.scrollWidth;
    if(!available || !needed || needed <= available + 2) return;

    const scale = Math.max(.42, Math.min(1, available / needed));
    frame.classList.add('is-scaled');
    frame.style.width = needed + 'px';
    frame.style.transform = 'scale(' + scale + ')';
    viewport.style.height = Math.ceil(frame.scrollHeight * scale) + 'px';
  }

  function fitAll(){
    viewports.forEach(fitGallery);
    if(!resizePinged){
      resizePinged = true;
      window.setTimeout(function(){
        window.dispatchEvent(new Event('resize'));
        viewports.forEach(fitGallery);
      }, 250);
    }
  }

  window.addEventListener('load', fitAll);
  window.addEventListener('resize', fitAll);
  window.setTimeout(fitAll, 250);
  window.setTimeout(fitAll, 900);
  window.setTimeout(fitAll, 1800);

  viewports.forEach(function(viewport){
    viewport.querySelectorAll('img').forEach(function(image){
      image.addEventListener('load', fitAll, { once:false });
    });

    if('MutationObserver' in window){
      const mutationObserver = new MutationObserver(function(){
        window.requestAnimationFrame(fitAll);
      });
      mutationObserver.observe(viewport, {
        childList:true,
        subtree:true
      });
    }
  });
})();

(function(){
  const revealItems = document.querySelectorAll('.bg-timeline-item, .bg-managed-event-card');
  if(!revealItems.length) return;

  if(!('IntersectionObserver' in window)){
    revealItems.forEach(function(item){
      item.classList.add('is-visible');
    });
    return;
  }

  const observer = new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if(entry.isIntersecting){
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.18 });

  revealItems.forEach(function(item){
    observer.observe(item);
  });
})();

// 导航栏滚动效果
window.addEventListener('scroll', function() {
    const nav = document.querySelector('nav');
    if (window.scrollY > 50) {
        nav.classList.add('scrolled');
    } else {
        nav.classList.remove('scrolled');
    }
});

document.getElementById('menu-btn').addEventListener('click', function() {
  document.getElementById('mobile-menu').classList.toggle('show');
});

document.querySelectorAll('.product-image').forEach(img => {
  img.addEventListener('mouseenter', function() {
      this.style.transform = 'scale(1.1)';
  });
  img.addEventListener('mouseleave', function() {
      this.style.transform = 'scale(1)';
  });
});

// 音频加载优化
const rbcSound = new Howl({
  src: ['sounds/rbc.mp3'],
  html5: true,
  preload: true,
  volume: 0.8
});

// 添加点击效果
document.querySelector('.rbc-avatar').addEventListener('click', function() {
  // 添加点击动画
  this.classList.add('animate-ping');
  setTimeout(() => this.classList.remove('animate-ping'), 500);
  
  // 播放声音
  if (rbcSound.state() === 'loaded') {
      rbcSound.play();
      console.log('✋😭✋想你了牢RBC✋😭✋');
  } else {
      console.log('音频加载中...');
      rbcSound.once('load', () => rbcSound.play());
  }
  
  // 添加特效
  const particles = document.createElement('div');
  particles.className = 'absolute inset-0 animate-pulse';
  this.parentElement.appendChild(particles);
  setTimeout(() => particles.remove(), 1000);
});

// 新增视差滚动效果
window.addEventListener('scroll', () => {
  document.querySelectorAll('.parallax').forEach(el => {
    const speed = parseFloat(el.dataset.speed) || 0.3;
    const yPos = -(window.pageYOffset * speed);
    el.style.transform = `translate3d(0, ${yPos}px, 0)`;
  });
});

// 新增卡片悬停3D效果
document.querySelectorAll('.product-card').forEach(card => {
  card.addEventListener('mousemove', (e) => {
    const rect = card.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    card.style.setProperty('--mouse-x', `${x}px`);
    card.style.setProperty('--mouse-y', `${y}px`);
  });
});


// 移动端触摸反馈
let touchTimer;
document.querySelector('.rbc-avatar').addEventListener('touchstart', () => {
  touchTimer = setTimeout(() => {
      document.querySelector('#rbc-sound').play();
  }, 100);
});

document.querySelector('.rbc-avatar').addEventListener('touchend', () => {
  clearTimeout(touchTimer);
});


// 图片懒加载优化
document.querySelectorAll('img').forEach(img => {
  img.addEventListener('load', function() {
      this.classList.add('loaded');
  });
});

// 在script.js中优化图片加载处理
document.querySelectorAll('.product-image-container img').forEach(img => {
  // 预加载完成后显示
  const tempImg = new Image();
  tempImg.src = img.src;
  tempImg.onload = () => {
      img.classList.add('product-image-loaded');
  };
  
  // 优化触摸设备体验
  let touchStartY = 0;
  img.addEventListener('touchstart', (e) => {
      touchStartY = e.touches[0].clientY;
  }, false);
  
  img.addEventListener('touchmove', (e) => {
      // 阻止垂直滚动时的误触
      const diffY = e.touches[0].clientY - touchStartY;
      if (Math.abs(diffY) > 5) return;
      e.preventDefault();
  }, { passive: false });
});



// 平滑滚动
// script.js 改进版
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function(e) {
      e.preventDefault();
      
      const targetId = this.getAttribute('href');
      const targetElement = document.querySelector(targetId);
      
      if (!targetElement) return;

      // 计算固定导航栏高度
      const headerHeight = document.querySelector('nav').offsetHeight;
      
      // 获取目标位置
      const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - headerHeight;
      
      // 自定义缓动函数
      function easeInOutQuad(t) {
          return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
      }

      // 动画参数
      const duration = 800;
      const start = window.pageYOffset;
      const distance = targetPosition - start;
      let startTime = null;

      // 动画循环
      function animation(currentTime) {
          if (!startTime) startTime = currentTime;
          const timeElapsed = currentTime - startTime;
          const progress = Math.min(timeElapsed / duration, 1);
          
          window.scrollTo(0, start + distance * easeInOutQuad(progress));
          
          if (timeElapsed < duration) {
              requestAnimationFrame(animation);
          } else {
              // 动画结束后更新 URL
              history.replaceState(null, null, targetId);
          }
      }

      // 启动动画
      requestAnimationFrame(animation);

      // 处理移动端菜单
      const mobileMenu = document.getElementById('mobile-menu');
      if (!mobileMenu.classList.contains('hidden')) {
          mobileMenu.classList.add('hidden');
      }
  });
});


document.addEventListener('click', function(e) {
    const menuBtn = document.getElementById('menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    if (!menuBtn.contains(e.target) && !mobileMenu.contains(e.target)) {
        mobileMenu.classList.add('hidden');
    }
});

document.querySelector('h1').style.background = 
  `linear-gradient(45deg, 
   ${getComputedStyle(document.documentElement)
     .getPropertyValue('--blue-400')}, 
   ${getComputedStyle(document.documentElement)
     .getPropertyValue('--cyan-300')})`;

document.querySelectorAll('iframe').forEach(iframe => {
    iframe.setAttribute('loading', 'lazy');
});

let checkTimer;
// 在广告代码中添加容错检测
window.addEventListener('load', () => {
    if(window.adsbygoogle && !adsbygoogle.loaded) {
      adsbygoogle.push({
        requestNonPersonalizedAds: 1
      });
    }
  });

  
function checkAdBlock() {
    return new Promise((resolve) => {
      // 方法一：检测常见广告类名
      const fakeAd = document.createElement('div');
      fakeAd.innerHTML = '&nbsp;';
      // 修改检测类名为最新屏蔽规则
      fakeAd.className = 'ad ads advertisement advert is-ad ad-banner ad-frame ad-placeholder';
      fakeAd.style.cssText = 'width: 1px !important; height: 1px !important; position: absolute !important; left: -10000px !important; top: -1000px !important;';
      document.body.appendChild(fakeAd);
      
      window.setTimeout(() => {
        const detected = fakeAd.offsetHeight === 0 || fakeAd.offsetWidth === 0;
        document.body.removeChild(fakeAd);
        resolve(detected);
      }, 100);
    });
  }
  
  // 方法二：检测Google广告对象
  function checkGoogleAds() {
    // 添加存在性验证
    if(typeof window.google === 'undefined') return false;
    return typeof google.adservices === 'undefined' && 
           typeof google.show_ads === 'undefined';
  }
  
  // 显示/隐藏提示条
  function toggleAdblockAlert(show) {
    const alert = document.getElementById('adblock-alert');
    alert.classList.toggle('hidden', !show);
    
    if(show) {
      localStorage.setItem('adblockAlertClosed', 'false');
    }
  }
  
// 添加存储时效性（24小时）
function closeAdblockAlert() {
  const expires = Date.now() + 86400000; 
  localStorage.setItem('adblockAlertClosed', expires);
}

window.addEventListener('beforeunload', () => {
  clearTimeout(checkTimer);
});

function checkBlockedRequests() {
  const adResources = [
    'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js',
    'https://adservice.google.com/adsid/integrator.js'
  ];
  
  return adResources.some(url => {
    try {
      new Image().src = url;
      return false;
    } catch(e) {
      return true;
    }
  });
}

  
  // 初始化检测
  window.addEventListener('load', () => {
    checkTimer = setTimeout(async () => {
      if(localStorage.getItem('adblockAlertClosed')) {
        const expires = parseInt(localStorage.getItem('adblockAlertClosed'));
        if(Date.now() < expires) return;
      }
      
      const isBlocked = await detectAdBlock();
      toggleAdblockAlert(isBlocked);
    }, 5000);
  });
// 在检测逻辑中添加广告位存在性检查
async function detectAdBlock() {
  try {
    const [method1, method2, method3, method4] = await Promise.all([
      checkAdBlock(),
      checkGoogleAds(),
      checkBlockedRequests(),
      checkAdPlaceholder()
    ]);
    
    console.log('检测结果:', {
      dom检测: method1,
      google对象: method2,
      请求拦截: method3,
      广告位状态: method4
    });
    
    return method1 || method2 || method3 || method4;
  } catch (error) {
    console.error('检测出错:', error);
    return false;
  }
}

function initAdblockCheck() {
  if(localStorage.getItem('adblockAlertClosed')) {
    const expires = parseInt(localStorage.getItem('adblockAlertClosed'));
    if(Date.now() < expires) return;
  }
  // 检查本地存储中是否存在 'adblockAlertClosed' 键
  if (localStorage.getItem('adblockAlertClosed')) {
    // 获取 'adblockAlertClosed' 键对应的值，并将其转换为整数
    const expires = parseInt(localStorage.getItem('adblockAlertClosed'));
    // 检查当前时间是否在免打扰期内
    if (Date.now() < expires) {
      console.log('在免打扰期内');
      return;
    }
  }
  
  // 等待所有资源加载完成
  if (document.readyState === 'complete') {
    // 如果文档已经加载完成，则立即开始检测
    startDetection();
  } else {
    // 如果文档尚未加载完成，则监听 'load' 事件，当文档加载完成时开始检测
    window.addEventListener('load', startDetection);
  }
}

function startDetection() {
  // 延长检测时间到广告位加载后
  checkTimer = setTimeout(async () => {
    const isBlocked = await detectAdBlock();
    console.log('最终检测结果:', isBlocked);
    toggleAdblockAlert(isBlocked);
    
    // 添加周期性重新检测
    if (!isBlocked) {
      setTimeout(() => detectAdBlock().then(toggleAdblockAlert), 15000);
    }
  }, 5000); // 延长到5秒后检测
}
document.addEventListener('DOMContentLoaded', initAdblockCheck);

if (document.readyState === 'complete') {
  startDetection();
} else {
  window.addEventListener('load', startDetection);
}
// 15秒后重新检测
setTimeout(() => detectAdBlock().then(toggleAdblockAlert), 9000);



function checkAdPlaceholder() {
  return new Promise(resolve => {
    const adElement = document.querySelector('.ad-placeholder');
    if (!adElement) {
      console.log('广告位元素不存在');
      return resolve(true);
    }

    // 添加异步检测确保样式加载完成
    requestAnimationFrame(() => {
      const style = window.getComputedStyle(adElement);
      const isHidden = style.display === 'none' || 
                      style.visibility === 'hidden' || 
                      style.opacity === '0' ||
                      adElement.offsetHeight === 0;
      
      // 增强内容检测
      const isContentModified = !/广告位|ad/i.test(adElement.textContent);
      
      console.log('广告位检测结果:', {isHidden, isContentModified});
      resolve(isHidden || isContentModified);
    });
  });
}

document.querySelectorAll('nav img, footer img').forEach(icon => {
  icon.addEventListener('click', function() {
      this.classList.add('animate-bounce');
      setTimeout(() => this.classList.remove('animate-bounce'), 1000);
  });
});


// 性能监控
window.addEventListener('load', () => {
  const perfData = window.performance.timing;
  const loadTime = perfData.loadEventEnd - perfData.navigationStart;
  
  if (loadTime < 2000) {
    console.log('页面加载性能优秀:', loadTime + 'ms');
  }
});

// 添加阅读进度跟踪（符合内容质量要求）
document.addEventListener('DOMContentLoaded', () => {
  const contentBlocks = document.querySelectorAll('article, .product-description');
  
  const trackEngagement = () => {
    const viewportHeight = window.innerHeight;
    let totalRead = 0;
    
    contentBlocks.forEach(block => {
      const rect = block.getBoundingClientRect();
      if (rect.top < viewportHeight && rect.bottom > 0) {
        const visibleHeight = Math.min(rect.bottom, viewportHeight) - Math.max(rect.top, 0);
        totalRead += visibleHeight / rect.height;
      }
    });
    
    if (totalRead / contentBlocks.length > 0.7) {
      console.log('用户完成70%内容阅读');
      // 可以在此处触发用户参与度统计
    }
  };
  
  window.addEventListener('scroll', trackEngagement);
});


// 页面加载动画
document.addEventListener('DOMContentLoaded', function() {
    // 可以添加更多的页面加载动画或功能
    console.log('思锐工作室官网已加载');
});



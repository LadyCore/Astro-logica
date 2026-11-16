(() => {
  const root = document.documentElement;
  const toggle = document.getElementById("theme-toggle");

  const current = () => root.getAttribute("data-theme") || "dark";

  const setTheme = (theme) => {
    root.setAttribute("data-theme", theme);
    try {
      localStorage.setItem("astro-theme", theme);
    } catch (_) {}
    drawStars();
  };

  if (toggle) {
    toggle.addEventListener("click", () => {
      setTheme(current() === "dark" ? "light" : "dark");
    });
  }

  const canvas = document.getElementById("starfield");
  let ctx;
  let stars = [];
  let w = 0;
  let h = 0;

  function resize() {
    if (!canvas) return;
    w = canvas.width = window.innerWidth * devicePixelRatio;
    h = canvas.height = window.innerHeight * devicePixelRatio;
    canvas.style.width = window.innerWidth + "px";
    canvas.style.height = window.innerHeight + "px";
    seed();
  }

  function seed() {
    const count = Math.min(180, Math.floor((w * h) / 16000));
    stars = Array.from({ length: count }, () => ({
      x: Math.random() * w,
      y: Math.random() * h,
      r: Math.random() * 1.35 * devicePixelRatio + 0.25,
      a: Math.random() * 0.75 + 0.2,
      s: Math.random() * 0.012 + 0.003,
      p: Math.random() * Math.PI * 2,
      gold: Math.random() > 0.82,
    }));
  }

  function drawStars() {
    if (!canvas || !ctx) return;
    const light = current() === "light";
    ctx.clearRect(0, 0, w, h);
    for (const star of stars) {
      const twinkle = 0.55 + Math.sin(performance.now() * star.s + star.p) * 0.45;
      ctx.beginPath();
      if (light) {
        ctx.fillStyle = star.gold
          ? `rgba(138, 106, 50, ${star.a * twinkle * 0.45})`
          : `rgba(60, 45, 25, ${star.a * twinkle * 0.35})`;
      } else {
        ctx.fillStyle = star.gold
          ? `rgba(228, 196, 138, ${star.a * twinkle})`
          : `rgba(180, 210, 255, ${star.a * twinkle * 0.75})`;
      }
      ctx.arc(star.x, star.y, star.r, 0, Math.PI * 2);
      ctx.fill();
    }
  }

  function loop() {
    drawStars();
    requestAnimationFrame(loop);
  }

  if (canvas) {
    ctx = canvas.getContext("2d");
    resize();
    loop();
    window.addEventListener("resize", () => {
      clearTimeout(window.__astroResize);
      window.__astroResize = setTimeout(resize, 120);
    });
  }
})();

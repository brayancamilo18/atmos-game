(() => {
  const appRoot = document.getElementById("gameApp");
  const canvas = document.getElementById("game");
  const ctx = canvas.getContext("2d");
  const startBtn = document.getElementById("startBtn");
  const leftBtn = document.getElementById("leftBtn");
  const rightBtn = document.getElementById("rightBtn");
  const leaderboardList = document.getElementById("leaderboardList");
  const personalBestEl = document.getElementById("personalBest");

  const W = canvas.width;
  const H = canvas.height;

  const keys = {
    left: false,
    right: false,
  };

  const STORAGE_KEY = "atmos-jump-highscore";
  const GAME_NAME = appRoot?.dataset.gameName || "atmos-jump";
  const GAME_VERSION = appRoot?.dataset.gameVersion || "1.0.0";
  const PLAYER_NAME = appRoot?.dataset.playerName || "Invitado";
  const IS_AUTHENTICATED = appRoot?.dataset.authenticated === "1";

  let game = null;
  let scoreAlreadySent = false;
  let gameStartAt = 0;

  function rand(min, max) {
    return Math.random() * (max - min) + min;
  }

  function clamp(v, min, max) {
    return Math.max(min, Math.min(max, v));
  }

  function rectsIntersect(a, b) {
    return (
      a.x < b.x + b.w &&
      a.x + a.w > b.x &&
      a.y < b.y + b.h &&
      a.y + a.h > b.y &&
      a.y + a.h > b.y
    );
  }

  function getHighScore() {
    return Number(localStorage.getItem(STORAGE_KEY) || 0);
  }

  function setHighScore(score) {
    localStorage.setItem(STORAGE_KEY, String(score));
  }

  function getCsrfToken() {
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    return tokenMeta ? tokenMeta.getAttribute("content") : "";
  }

  function detectPlatform() {
    const ua = navigator.userAgent.toLowerCase();
    if (/android|iphone|ipad|ipod|mobile/.test(ua)) return "mobile";
    return "web";
  }

  function getClientUuid() {
    let id = localStorage.getItem("atmos_jump_client_uuid");

    if (!id) {
      if (window.crypto && crypto.randomUUID) {
        id = crypto.randomUUID();
      } else {
        id = `client-${Date.now()}-${Math.random().toString(36).slice(2)}`;
      }
      localStorage.setItem("atmos_jump_client_uuid", id);
    }

    return id;
  }

  async function apiFetch(url, options = {}) {
    const headers = {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
      ...(options.headers || {}),
    };

    if ((options.method || "GET").toUpperCase() !== "GET") {
      headers["Content-Type"] = "application/json";
      const csrf = getCsrfToken();
      if (csrf) headers["X-CSRF-TOKEN"] = csrf;
    }

    return fetch(url, {
      credentials: "same-origin",
      ...options,
      headers,
    });
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, (char) => {
      const map = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
      };
      return map[char];
    });
  }

  async function loadLeaderboard() {
    if (!leaderboardList) return;

    try {
      const res = await apiFetch(
        `/api/leaderboard?game=${encodeURIComponent(GAME_NAME)}&limit=7`,
      );
      if (!res.ok) {
        leaderboardList.innerHTML = `<div class="leaderboard-empty">No se pudo cargar.</div>`;
        return;
      }

      const data = await res.json();
      renderLeaderboard(data.leaderboard || []);
    } catch (error) {
      console.error("No se pudo cargar leaderboard", error);
      leaderboardList.innerHTML = `<div class="leaderboard-empty">No se pudo cargar.</div>`;
    }
  }

  async function loadMyScores() {
    if (!personalBestEl) return;

    try {
      const res = await apiFetch(`/my-scores?game=${encodeURIComponent(GAME_NAME)}`);
      if (!res.ok) return;

      const data = await res.json();
      personalBestEl.textContent = `${data.best_height || 0} m`;
    } catch (error) {
      console.error("No se pudo cargar score personal", error);
    }
  }

  function renderLeaderboard(rows) {
    if (!leaderboardList) return;

    leaderboardList.innerHTML = "";

    if (!rows.length) {
      leaderboardList.innerHTML = `<div class="leaderboard-empty">Aún no hay récords.</div>`;
      return;
    }

    rows.forEach((row) => {
      const item = document.createElement("div");
      item.className = "leaderboard-item";
      item.innerHTML = `
        <span class="leaderboard-rank">#${row.rank}</span>
        <span class="leaderboard-name">${escapeHtml(row.player_name)}</span>
        <strong class="leaderboard-height">${row.best_height} m</strong>
      `;
      leaderboardList.appendChild(item);
    });
  }

  async function submitScore() {
    if (!game || !game.over || scoreAlreadySent) return;

    scoreAlreadySent = true;

    const payload = {
      game: GAME_NAME,
      height: game.bestHeight,
      score: game.score,
      duration_ms: Math.max(0, Date.now() - gameStartAt),
      player_name: PLAYER_NAME,
      client_uuid: getClientUuid(),
      game_version: GAME_VERSION,
      platform: detectPlatform(),
    };

    try {
      const res = await apiFetch("/scores", {
        method: "POST",
        body: JSON.stringify(payload),
      });

      if (!res.ok) {
        const errorText = await res.text();
        console.error("Error guardando score:", errorText);
        return;
      }

      const data = await res.json();

      if (personalBestEl) {
        personalBestEl.textContent = `${data.personal_best || game.bestHeight} m`;
      }

      loadLeaderboard();
    } catch (error) {
      console.error("Error de red al guardar score:", error);
    }
  }

  function triggerGameOver() {
    if (!game || game.over) return;
    game.over = true;
    game.player.alive = false;
    submitScore();
  }

  // 10 capas
  function getLayerByHeight(h) {
    if (h < 1500) {
      return {
        name: "Cielo bajo",
        skyTop: "#7fd1ff",
        skyBottom: "#dff6ff",
        accent: "#ffffff",
        obstacleTypes: ["bird"],
        wind: 0,
        gravityMul: 1,
      };
    }

    if (h < 3000) {
      return {
        name: "Nubes altas",
        skyTop: "#6ac6ff",
        skyBottom: "#c9f0ff",
        accent: "#f7feff",
        obstacleTypes: ["bird", "drone"],
        wind: 0.02,
        gravityMul: 1,
      };
    }

    if (h < 5000) {
      return {
        name: "Troposfera",
        skyTop: "#5aaeff",
        skyBottom: "#b7e3ff",
        accent: "#eefbff",
        obstacleTypes: ["bird", "drone"],
        wind: 0.035,
        gravityMul: 1.01,
      };
    }

    if (h < 8000) {
      return {
        name: "Tropopausa",
        skyTop: "#4a8fe8",
        skyBottom: "#9fc7ff",
        accent: "#e8f1ff",
        obstacleTypes: ["drone", "plane"],
        wind: 0.045,
        gravityMul: 1.02,
      };
    }

    if (h < 11000) {
      return {
        name: "Estratosfera baja",
        skyTop: "#3b73d2",
        skyBottom: "#86b3ff",
        accent: "#e6eeff",
        obstacleTypes: ["drone", "plane"],
        wind: 0.055,
        gravityMul: 1.03,
      };
    }

    if (h < 15000) {
      return {
        name: "Estratosfera alta",
        skyTop: "#2e56b8",
        skyBottom: "#6ea1ff",
        accent: "#dde8ff",
        obstacleTypes: ["plane", "meteor"],
        wind: 0.07,
        gravityMul: 1.04,
      };
    }

    if (h < 20000) {
      return {
        name: "Mesosfera baja",
        skyTop: "#1d3d92",
        skyBottom: "#4d7be0",
        accent: "#dae4ff",
        obstacleTypes: ["plane", "meteor"],
        wind: 0.085,
        gravityMul: 1.03,
      };
    }

    if (h < 26000) {
      return {
        name: "Mesosfera alta",
        skyTop: "#122a6e",
        skyBottom: "#345fc2",
        accent: "#d3ddff",
        obstacleTypes: ["meteor", "satellite"],
        wind: 0.1,
        gravityMul: 1.01,
      };
    }

    if (h < 33000) {
      return {
        name: "Termosfera",
        skyTop: "#0b123d",
        skyBottom: "#1f3b8d",
        accent: "#b5c8ff",
        obstacleTypes: ["satellite", "meteor"],
        wind: 0.06,
        gravityMul: 0.98,
      };
    }

    return {
      name: "Espacio profundo",
      skyTop: "#02030a",
      skyBottom: "#11193d",
      accent: "#8db5ff",
      obstacleTypes: ["satellite", "meteor"],
      wind: 0.02,
      gravityMul: 0.92,
    };
  }

  function createPlayer() {
    return {
      x: W / 2 - 16,
      y: H - 120,
      w: 32,
      h: 42,
      vx: 0,
      vy: 0,
      speed: 0.52,
      maxSpeed: 4.8,
      jumpForce: -11.8,
      alive: true,
      trail: [],
    };
  }

  function createPlatform(x, y, type = "normal") {
    let w = 90;
    let h = 14;

    if (type === "small") w = 60;
    if (type === "moving") w = 85;
    if (type === "fragile") w = 78;
    if (type === "boost") w = 82;

    return {
      x,
      y,
      w,
      h,
      type,
      vx: type === "moving" ? rand(-1.2, 1.2) : 0,
      broken: false,
      alpha: 1,
    };
  }

  function createObstacle(layerName, x, y, type) {
    let base = { x, y, w: 36, h: 22, type, vx: 0, vy: 0 };

    if (type === "bird") {
      base.w = 30;
      base.h = 18;
      base.vx = rand(-1.3, 1.3) || 1;
    } else if (type === "drone") {
      base.w = 32;
      base.h = 18;
      base.vx = rand(-1.5, 1.5) || -1.1;
    } else if (type === "plane") {
      base.w = 58;
      base.h = 18;
      base.vx = rand(-1.9, 1.9) || 1.5;
    } else if (type === "meteor") {
      base.w = 24;
      base.h = 24;
      base.vy = rand(1.0, 2.1);
      base.vx = rand(-0.6, 0.6);
    } else if (type === "satellite") {
      base.w = 48;
      base.h = 20;
      base.vx = rand(-1.3, 1.3) || 1.0;
    }

    base.layer = layerName;
    return base;
  }

  function randomPlatformType(height) {
    const r = Math.random();

    if (height < 2000) {
      if (r < 0.82) return "normal";
      if (r < 0.94) return "small";
      return "moving";
    }

    if (height < 6000) {
      if (r < 0.68) return "normal";
      if (r < 0.83) return "small";
      if (r < 0.95) return "moving";
      return "fragile";
    }

    if (height < 12000) {
      if (r < 0.52) return "normal";
      if (r < 0.68) return "small";
      if (r < 0.84) return "moving";
      if (r < 0.95) return "fragile";
      return "boost";
    }

    if (r < 0.4) return "normal";
    if (r < 0.58) return "small";
    if (r < 0.76) return "moving";
    if (r < 0.9) return "fragile";
    return "boost";
  }

  function getMaxObstacles(height) {
    if (height < 2500) return 0;
    if (height < 5000) return 1;
    if (height < 9000) return 2;
    if (height < 16000) return 3;
    if (height < 26000) return 4;
    return 5;
  }

  function getAllowedObstacleTypes(height, layerObstacleTypes) {
    if (height < 2500) return [];

    if (height < 5000) {
      return layerObstacleTypes.filter((t) => t === "bird");
    }

    if (height < 9000) {
      return layerObstacleTypes.filter((t) => t === "bird" || t === "drone");
    }

    if (height < 15000) {
      return layerObstacleTypes.filter((t) => t !== "satellite");
    }

    return layerObstacleTypes;
  }

  function resetGame() {
    scoreAlreadySent = false;
    gameStartAt = Date.now();

    game = {
      started: true,
      over: false,
      paused: false,
      player: createPlayer(),
      cameraY: 0,
      worldHeight: 0,
      bestHeight: 0,
      score: 0,
      highScore: getHighScore(),
      platforms: [],
      obstacles: [],
      stars: [],
      clouds: [],
      particles: [],
      obstacleTimer: 0,
    };

    game.platforms.push(createPlatform(W / 2 - 45, H - 70, "normal"));

    let y = H - 140;
    for (let i = 0; i < 14; i++) {
      const x = rand(20, W - 110);
      const type = i < 6 ? "normal" : randomPlatformType(0);
      game.platforms.push(createPlatform(x, y, type));
      y -= rand(60, 86);
    }

    for (let i = 0; i < 60; i++) {
      game.stars.push({
        x: rand(0, W),
        y: rand(-3000, H),
        r: rand(0.6, 2.2),
        a: rand(0.2, 1),
      });
    }

    for (let i = 0; i < 14; i++) {
      game.clouds.push({
        x: rand(0, W),
        y: rand(-1200, H),
        w: rand(60, 130),
        h: rand(22, 44),
        speed: rand(0.1, 0.45),
        alpha: rand(0.18, 0.35),
      });
    }
  }

  function spawnPlatformsIfNeeded() {
    let highestY = Infinity;
    for (const p of game.platforms) {
      if (p.y < highestY) highestY = p.y;
    }

    while (highestY > game.cameraY - 900) {
      const height = Math.floor(game.bestHeight);
      const difficulty = Math.min(1, Math.max(0, (height - 1500) / 32000));

      const gapY = rand(62, 86 + difficulty * 18);
      highestY -= gapY;

      let maxPlatformWidth = 110;
      if (height > 6000) maxPlatformWidth = 102;
      if (height > 14000) maxPlatformWidth = 95;

      const x = rand(16, W - maxPlatformWidth - 16);
      const type = randomPlatformType(height);
      game.platforms.push(createPlatform(clamp(x, 12, W - 100), highestY, type));

      const canSpawnObstacleFromPlatforms =
        height > 2200 &&
        Math.random() < (0.015 + difficulty * 0.08) &&
        game.obstacles.length < getMaxObstacles(height);

      if (canSpawnObstacleFromPlatforms) {
        const layer = getLayerByHeight(height);
        const allowedTypes = getAllowedObstacleTypes(height, layer.obstacleTypes);

        if (allowedTypes.length) {
          const obstacleType =
            allowedTypes[Math.floor(Math.random() * allowedTypes.length)];

          game.obstacles.push(
            createObstacle(
              layer.name,
              rand(20, W - 70),
              highestY - rand(40, 110),
              obstacleType,
            ),
          );
        }
      }
    }

    game.platforms = game.platforms.filter(
      (p) => p.y < game.cameraY + H + 120 && !p.removed,
    );

    game.obstacles = game.obstacles.filter(
      (o) => o.y < game.cameraY + H + 150 && o.y > game.cameraY - 900,
    );
  }

  function handleInput() {
    const p = game.player;

    if (keys.left) p.vx -= p.speed;
    if (keys.right) p.vx += p.speed;

    if (!keys.left && !keys.right) {
      p.vx *= 0.9;
    }

    p.vx = clamp(p.vx, -p.maxSpeed, p.maxSpeed);
  }

  function updatePlayer(layer) {
    const p = game.player;

    p.vy += 0.42 * layer.gravityMul;
    p.x += p.vx;
    p.y += p.vy;

    if (layer.wind !== 0) {
      p.x += Math.sin(performance.now() * 0.0012 + p.y * 0.01) * layer.wind;
    }

    if (p.x + p.w < 0) p.x = W;
    if (p.x > W) p.x = -p.w;

    if (p.vy > 0) {
      for (const platform of game.platforms) {
        if (platform.broken) continue;

        const feetPrev = p.y + p.h - p.vy;
        const feetNow = p.y + p.h;

        const landing =
          p.x + p.w > platform.x + 6 &&
          p.x < platform.x + platform.w - 6 &&
          feetPrev <= platform.y &&
          feetNow >= platform.y;

        if (landing) {
          p.y = platform.y - p.h;
          p.vy = p.jumpForce;

          if (platform.type === "boost") p.vy = p.jumpForce - 4;
          if (platform.type === "fragile") platform.broken = true;
          break;
        }
      }
    }

    for (const obs of game.obstacles) {
      if (rectsIntersect(p, obs)) {
        triggerGameOver();
        return;
      }
    }

    if (p.y - game.cameraY > H + 80) {
      triggerGameOver();
      return;
    }

    if (p.y < H * 0.42 + game.cameraY) {
      const diff = H * 0.42 + game.cameraY - p.y;
      game.cameraY -= diff;
      p.y += diff;
      game.worldHeight = Math.max(game.worldHeight, -game.cameraY);
      game.bestHeight = Math.max(game.bestHeight, Math.floor(game.worldHeight));
      game.score = Math.floor(game.bestHeight / 10);
    }

    p.trail.push({ x: p.x + p.w / 2, y: p.y + p.h, a: 0.6 });
    if (p.trail.length > 10) p.trail.shift();
  }

  function updatePlatforms() {
    for (const p of game.platforms) {
      if (p.type === "moving") {
        p.x += p.vx;
        if (p.x < 0 || p.x + p.w > W) p.vx *= -1;
      }

      if (p.broken) {
        p.alpha -= 0.06;
        if (p.alpha <= 0) p.removed = true;
      }
    }
  }

  function updateObstacles(layer) {
    for (const o of game.obstacles) {
      o.x += o.vx;
      o.y += o.vy;

      if (
        o.type === "bird" ||
        o.type === "drone" ||
        o.type === "plane" ||
        o.type === "satellite"
      ) {
        if (o.x < -80) o.x = W + 20;
        if (o.x > W + 80) o.x = -60;
      }

      if (o.type === "meteor" && o.y > game.cameraY + H + 100) {
        o.y = game.cameraY - rand(220, 520);
        o.x = rand(20, W - 40);
      }
    }

    const height = game.bestHeight;

    if (height < 2500) return;

    game.obstacleTimer += 1;

    let spawnRate = 999999;

    if (height < 5000) spawnRate = 220;
    else if (height < 9000) spawnRate = 170;
    else if (height < 15000) spawnRate = 130;
    else if (height < 24000) spawnRate = 95;
    else spawnRate = 70;

    if (game.obstacleTimer >= spawnRate) {
      game.obstacleTimer = 0;

      const maxObstacles = getMaxObstacles(height);
      if (game.obstacles.length >= maxObstacles) return;

      const allowedTypes = getAllowedObstacleTypes(height, layer.obstacleTypes);
      if (!allowedTypes.length) return;

      const type = allowedTypes[Math.floor(Math.random() * allowedTypes.length)];
      const y = game.cameraY - rand(140, 260);
      const x = rand(20, W - 70);

      game.obstacles.push(createObstacle(layer.name, x, y, type));
    }
  }

  function updateParticles() {
    for (const t of game.player.trail) {
      t.a -= 0.04;
    }
    game.player.trail = game.player.trail.filter((t) => t.a > 0.02);
  }

  function updateBackground(layer) {
    for (const c of game.clouds) {
      c.x += c.speed + layer.wind * 6;
      if (c.x > W + 140) c.x = -c.w - 10;
    }
  }

  function drawGradient(layer) {
    const g = ctx.createLinearGradient(0, 0, 0, H);
    g.addColorStop(0, layer.skyTop);
    g.addColorStop(1, layer.skyBottom);
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, W, H);
  }

  function drawCloud(x, y, w, h, alpha) {
    ctx.save();
    ctx.globalAlpha = alpha;
    ctx.fillStyle = "#ffffff";
    ctx.beginPath();
    ctx.ellipse(x, y, w * 0.26, h * 0.45, 0, 0, Math.PI * 2);
    ctx.ellipse(x + w * 0.18, y - 8, w * 0.22, h * 0.4, 0, 0, Math.PI * 2);
    ctx.ellipse(x + w * 0.4, y, w * 0.28, h * 0.48, 0, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();
  }

  function drawPlanet(x, y, r, color, ring = false) {
    ctx.save();
    ctx.fillStyle = color;
    ctx.beginPath();
    ctx.arc(x, y, r, 0, Math.PI * 2);
    ctx.fill();

    if (ring) {
      ctx.strokeStyle = "rgba(220,220,255,0.65)";
      ctx.lineWidth = 3;
      ctx.beginPath();
      ctx.ellipse(x, y, r + 10, r * 0.45, -0.2, 0, Math.PI * 2);
      ctx.stroke();
    }

    ctx.restore();
  }

  function drawBackground(layer) {
    drawGradient(layer);

    const h = game.bestHeight;

    if (h >= 2500) {
      for (const s of game.stars) {
        const sy = s.y - game.cameraY * 0.08;
        if (sy > -10 && sy < H + 10) {
          ctx.globalAlpha = s.a;
          ctx.fillStyle = "#ffffff";
          ctx.beginPath();
          ctx.arc(s.x, sy, s.r, 0, Math.PI * 2);
          ctx.fill();
        }
      }
      ctx.globalAlpha = 1;
    }

    if (h < 18000) {
      for (const c of game.clouds) {
        const cy = c.y - game.cameraY * 0.3;
        if (cy > -80 && cy < H + 80) {
          drawCloud(c.x, cy, c.w, c.h, c.alpha);
        }
      }
    }

    if (h > 9000) drawPlanet(330, 140, 22, "#f7d37c");
    if (h > 18000) drawPlanet(80, 170, 30, "#78a7ff");
    if (h > 30000) drawPlanet(320, 300, 36, "#c98cff", true);
  }

  function drawPlatforms() {
    for (const p of game.platforms) {
      const sy = p.y - game.cameraY;
      if (sy > H + 30 || sy < -40) continue;

      ctx.save();
      ctx.globalAlpha = p.alpha;

      if (p.type === "normal") ctx.fillStyle = "#5be37d";
      if (p.type === "small") ctx.fillStyle = "#4cc9f0";
      if (p.type === "moving") ctx.fillStyle = "#f9c74f";
      if (p.type === "fragile") ctx.fillStyle = "#ff7b7b";
      if (p.type === "boost") ctx.fillStyle = "#a86bff";

      ctx.fillRect(p.x, sy, p.w, p.h);
      ctx.fillStyle = "rgba(255,255,255,0.25)";
      ctx.fillRect(p.x + 4, sy + 3, p.w - 8, 3);
      ctx.restore();
    }
  }

  function drawObstacle(o) {
    const sy = o.y - game.cameraY;
    ctx.save();

    if (o.type === "bird") {
      ctx.fillStyle = "#2b2d42";
      ctx.beginPath();
      ctx.arc(o.x + 8, sy + 10, 7, 0, Math.PI * 2);
      ctx.arc(o.x + 20, sy + 9, 6, 0, Math.PI * 2);
      ctx.fill();
      ctx.strokeStyle = "#2b2d42";
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.moveTo(o.x + 4, sy + 9);
      ctx.lineTo(o.x - 4, sy + 4);
      ctx.moveTo(o.x + 23, sy + 9);
      ctx.lineTo(o.x + 31, sy + 4);
      ctx.stroke();
    } else if (o.type === "drone") {
      ctx.fillStyle = "#111827";
      ctx.fillRect(o.x + 8, sy + 6, 16, 8);
      ctx.fillRect(o.x, sy + 2, 8, 3);
      ctx.fillRect(o.x + 24, sy + 2, 8, 3);
      ctx.fillStyle = "#7dd3fc";
      ctx.fillRect(o.x + 12, sy + 8, 8, 4);
    } else if (o.type === "plane") {
      ctx.fillStyle = "#dfe7ff";
      ctx.fillRect(o.x, sy + 7, 50, 6);
      ctx.fillRect(o.x + 16, sy + 1, 16, 18);
      ctx.fillRect(o.x + 36, sy + 5, 16, 4);
    } else if (o.type === "meteor") {
      ctx.fillStyle = "#8d6e63";
      ctx.beginPath();
      ctx.arc(o.x + 12, sy + 12, 12, 0, Math.PI * 2);
      ctx.fill();
      ctx.strokeStyle = "#ffb703";
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.moveTo(o.x + 22, sy + 6);
      ctx.lineTo(o.x + 34, sy - 2);
      ctx.stroke();
    } else if (o.type === "satellite") {
      ctx.fillStyle = "#c7d2fe";
      ctx.fillRect(o.x + 16, sy + 6, 16, 10);
      ctx.fillStyle = "#60a5fa";
      ctx.fillRect(o.x, sy + 5, 12, 12);
      ctx.fillRect(o.x + 36, sy + 5, 12, 12);
    }

    ctx.restore();
  }

  function drawObstacles() {
    for (const o of game.obstacles) {
      const sy = o.y - game.cameraY;
      if (sy < -60 || sy > H + 60) continue;
      drawObstacle(o);
    }
  }

  function drawPlayer() {
    const p = game.player;
    const sy = p.y - game.cameraY;

    for (const t of p.trail) {
      ctx.save();
      ctx.globalAlpha = t.a;
      ctx.fillStyle = "#ffffff";
      ctx.beginPath();
      ctx.arc(t.x, t.y - game.cameraY, 3, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
    }

    ctx.save();

    ctx.fillStyle = "#ffdc66";
    ctx.fillRect(p.x + 4, sy + 10, 24, 24);

    ctx.fillStyle = "#ffd166";
    ctx.beginPath();
    ctx.arc(p.x + 16, sy + 10, 12, 0, Math.PI * 2);
    ctx.fill();

    ctx.fillStyle = "#1f2937";
    ctx.fillRect(p.x + 9, sy + 38, 5, 10);
    ctx.fillRect(p.x + 18, sy + 38, 5, 10);

    ctx.fillStyle = "#1f2937";
    ctx.fillRect(p.x + 2, sy + 16, 6, 4);
    ctx.fillRect(p.x + 24, sy + 16, 6, 4);

    ctx.fillStyle = "#111827";
    ctx.fillRect(p.x + 12, sy + 7, 3, 3);
    ctx.fillRect(p.x + 18, sy + 7, 3, 3);

    ctx.restore();
  }

  function drawHUD() {
    const layer = getLayerByHeight(game.bestHeight);

    ctx.save();
    ctx.fillStyle = "rgba(7, 12, 27, 0.35)";
    ctx.fillRect(12, 12, 170, 84);
    ctx.restore();

    ctx.fillStyle = "#ffffff";
    ctx.font = "bold 18px Arial";
    ctx.fillText(`Altura: ${game.bestHeight} m`, 22, 38);

    ctx.font = "14px Arial";
    ctx.fillText(`Puntos: ${game.score}`, 22, 58);
    ctx.fillText(`Récord: ${game.highScore} m`, 22, 78);

    ctx.textAlign = "right";
    ctx.fillText(layer.name, W - 20, 34);
    ctx.textAlign = "left";
  }

  function drawStartOverlay() {
    ctx.fillStyle = "rgba(4,8,20,0.45)";
    ctx.fillRect(0, 0, W, H);

    ctx.fillStyle = "#fff";
    ctx.textAlign = "center";
    ctx.font = "bold 34px Arial";
    ctx.fillText("Atmos Jump", W / 2, H / 2 - 80);

    ctx.font = "16px Arial";
    ctx.fillText("Sube sin fin por las capas de la atmósfera", W / 2, H / 2 - 40);
    ctx.fillText("Evita obstáculos y pisa plataformas", W / 2, H / 2 - 15);

    ctx.font = "bold 18px Arial";
    ctx.fillText("Pulsa Jugar o ENTER", W / 2, H / 2 + 35);

    ctx.font = "14px Arial";
    ctx.fillText("Toca izquierda o derecha en móvil", W / 2, H / 2 + 68);
    ctx.textAlign = "left";
  }

  function drawGameOver() {
    ctx.fillStyle = "rgba(4,8,20,0.52)";
    ctx.fillRect(0, 0, W, H);

    ctx.fillStyle = "#ffffff";
    ctx.textAlign = "center";
    ctx.font = "bold 34px Arial";
    ctx.fillText("Game Over", W / 2, H / 2 - 50);

    ctx.font = "18px Arial";
    ctx.fillText(`Altura final: ${game.bestHeight} m`, W / 2, H / 2 - 10);
    ctx.fillText(`Récord: ${game.highScore} m`, W / 2, H / 2 + 20);

    ctx.font = "bold 16px Arial";
    ctx.fillText("Pulsa R o toca Jugar", W / 2, H / 2 + 70);
    ctx.textAlign = "left";
  }

  function drawPause() {
    ctx.fillStyle = "rgba(4,8,20,0.3)";
    ctx.fillRect(0, 0, W, H);

    ctx.fillStyle = "#fff";
    ctx.textAlign = "center";
    ctx.font = "bold 26px Arial";
    ctx.fillText("Pausa", W / 2, H / 2);
    ctx.textAlign = "left";
  }

  function render() {
    if (!game) {
      const layer = getLayerByHeight(0);
      drawGradient(layer);
      drawStartOverlay();
      return;
    }

    const layer = getLayerByHeight(game.bestHeight);

    drawBackground(layer);
    drawPlatforms();
    drawObstacles();
    drawPlayer();
    drawHUD();

    if (game.paused) drawPause();
    if (game.over) drawGameOver();
  }

  function update() {
    if (!game || game.over || game.paused) return;

    const layer = getLayerByHeight(game.bestHeight);

    handleInput();
    updatePlayer(layer);
    updatePlatforms();
    updateObstacles(layer);
    updateParticles();
    updateBackground(layer);
    spawnPlatformsIfNeeded();

    if (game.bestHeight > game.highScore) {
      game.highScore = game.bestHeight;
      setHighScore(game.highScore);
    }
  }

  // ===== Prevent selection / gestures inside game UI =====
  function preventDefaultIfPossible(e) {
    if (!e) return;
    try {
      e.preventDefault();
    } catch (_) {}
  }

  const disableSelectTargets = [
    document.getElementById("gameApp"),
    document.querySelector(".game-card"),
    document.querySelector(".glass-panel"),
    canvas,
  ].filter(Boolean);

  disableSelectTargets.forEach((el) => {
    el.addEventListener("selectstart", preventDefaultIfPossible);
    el.addEventListener("dragstart", preventDefaultIfPossible);
  });

  canvas.addEventListener("touchstart", preventDefaultIfPossible, { passive: false });
  canvas.addEventListener("touchmove", preventDefaultIfPossible, { passive: false });
  canvas.addEventListener("gesturestart", preventDefaultIfPossible, { passive: false });
  canvas.addEventListener("gesturechange", preventDefaultIfPossible, { passive: false });
  canvas.addEventListener("gestureend", preventDefaultIfPossible, { passive: false });

  // ===== Fixed timestep loop (same gameplay everywhere) =====
  const FIXED_FPS = 60;
  const FIXED_STEP_MS = 1000 / FIXED_FPS;

  let lastFrameAt = performance.now();
  let accumulatorMs = 0;

  function loop(now = performance.now()) {
    let frameMs = now - lastFrameAt;
    lastFrameAt = now;

    frameMs = Math.min(frameMs, 100);
    accumulatorMs += frameMs;

    while (accumulatorMs >= FIXED_STEP_MS) {
      update();
      accumulatorMs -= FIXED_STEP_MS;
    }

    render();
    requestAnimationFrame(loop);
  }

  document.addEventListener("keydown", (e) => {
    const key = e.key.toLowerCase();

    if (key === "arrowleft" || key === "a") keys.left = true;
    if (key === "arrowright" || key === "d") keys.right = true;

    if (key === "p" && game && !game.over) {
      game.paused = !game.paused;
    }

    if (key === "r" && game && game.over) {
      resetGame();
    }

    if (key === "enter" && !game) {
      resetGame();
    }
  });

  document.addEventListener("keyup", (e) => {
    const key = e.key.toLowerCase();

    if (key === "arrowleft" || key === "a") keys.left = false;
    if (key === "arrowright" || key === "d") keys.right = false;
  });

  function bindHoldButton(button, direction) {
    if (!button) return;

    const press = (e) => {
      e.preventDefault();
      if (direction === "left") keys.left = true;
      if (direction === "right") keys.right = true;
    };

    const release = (e) => {
      e.preventDefault();
      if (direction === "left") keys.left = false;
      if (direction === "right") keys.right = false;
    };

    button.addEventListener("pointerdown", press);
    button.addEventListener("pointerup", release);
    button.addEventListener("pointercancel", release);
    button.addEventListener("pointerleave", release);
  }

  bindHoldButton(leftBtn, "left");
  bindHoldButton(rightBtn, "right");

  startBtn.addEventListener("click", () => {
    resetGame();
  });

  canvas.addEventListener("dblclick", (e) => e.preventDefault());
  canvas.addEventListener("contextmenu", (e) => e.preventDefault());

  loadLeaderboard();
  loadMyScores();
  render();
  requestAnimationFrame(loop);
})();
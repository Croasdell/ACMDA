class Thpace {
  constructor(canvas, settings) {
    if (!canvas) {
      console.log('Need a valid canvas element.');
      return;
    }
    this.canvas = canvas;
    this.ctx = canvas.getContext('2d');

    this.triangles = [];
    this.particles = [];
    this.coordinateTable = {};
    this.baseCoordinateTable = {};
    this.settings = extend({
      triangleSize: 130,
      bleed: 200,
      noise: 60,
      color1: '#0b0b0b',
      color2: '#1e1e1e',
      pointVariationX: 20,
      pointVariationY: 35,
      pointAnimationSpeed: 15,
      image: false,
      imageOpacity: 0.4
    }, settings);

    window.addEventListener('resize', this.resize.bind(this));
    this.resize();

    this.gradientOpacity = 1;
  }

  animate() {
    const ctx = this.ctx;

    ctx.clearRect(0, 0, this.width, this.height);

    this.triangles.forEach((t) => {
      ctx.beginPath();

      const coords = [];
      coords.push({ x: t[0][0], y: t[0][1] });
      coords.push({ x: t[1][0], y: t[1][1] });
      coords.push({ x: t[2][0], y: t[2][1] });

      const color = t[3];

      ctx.fillStyle = color;
      ctx.strokeStyle = color;

      const dp = [0, 1, 2, 0];
      dp.forEach((el, ind) => {
        if (this.coordinateTable[coords[el].x] && this.coordinateTable[coords[el].x][coords[el].y] !== undefined) {
          const c = { x: coords[el].x, y: coords[el].y };
          const change = this.coordinateTable[coords[el].x][coords[el].y];

          if (ind === 0) {
            ctx.moveTo(c.x + change.x, c.y + change.y);
          } else {
            ctx.lineTo(c.x + change.x, c.y + change.y);
          }
        }
      });

      ctx.fill();
      ctx.stroke();
    });

    this.particles.forEach((p) => {
      p.update();
    });

    this.particles.forEach((p) => {
      p.draw();
    });

    if (this.settings.image) {
      const pat = ctx.createPattern(this.settings.image, 'repeat');
      ctx.globalAlpha = this.settings.imageOpacity;
      ctx.fillStyle = pat;
      ctx.fillRect(0, 0, this.width, this.height);
      ctx.globalAlpha = 1;
    }

    this.animateCoordinateTable();
    requestAnimationFrame(this.animate.bind(this));
  }

  start() {
    this.animate();
  }

  generateTriangles() {
    const points = [];
    const coordinateTable = {};
    points.push([0, 0]);
    points.push([0, this.height]);
    points.push([this.width, 0]);
    points.push([this.width, this.height]);

    const bleed = this.settings.bleed;
    const size = this.settings.triangleSize;
    const noise = this.settings.noise;

    for (let i = 0 - bleed; i < this.width + bleed; i += size) {
      for (let j = 0 - bleed; j < this.height + bleed; j += size) {
        const x = i + getRandomInt(0, noise);
        const y = j + getRandomInt(0, noise);
        points.push([x, y]);
      }
    }

    const delaunay = Delaunator.from(points);
    const triangleList = delaunay.triangles;

    const coordinates = [];

    for (let i = 0; i < triangleList.length; i += 3) {
      const t = [
        points[triangleList[i]],
        points[triangleList[i + 1]],
        points[triangleList[i + 2]]
      ];

      const coords = [];
      coords.push({ x: t[0][0], y: t[0][1] });
      coords.push({ x: t[1][0], y: t[1][1] });
      coords.push({ x: t[2][0], y: t[2][1] });

      const color = gradient(getCenter(coords), this.width, this.height, this.settings.color1, this.settings.color2);

      t.push(color);
      coordinates.push(t);
    }

    const baseCoordinateTable = {};
    coordinates.forEach((t) => {
      t.forEach((p) => {
        const x = p[0];
        const y = p[1];

        if (!coordinateTable[x]) {
          coordinateTable[x] = {};
        }

        const per = x / this.width;

        coordinateTable[x][y] = 0;

        if (!baseCoordinateTable[x]) {
          baseCoordinateTable[x] = {};
        }
        baseCoordinateTable[x][y] = per * 2 * Math.PI;
      });
    });

    this.triangles = coordinates;
    this.coordinateTable = coordinateTable;
    this.baseCoordinateTable = baseCoordinateTable;
  }

  generateParticles() {
    const particles = [];
    for (let i = 0; i < 250; i++) {
      const pSet = {
        ctx: this.ctx,
        width: this.width,
        height: this.height
      };
      particles.push(new Particle(pSet));
    }
    this.particles = particles;
  }

  animateCoordinateTable() {
    Object.keys(this.coordinateTable).forEach((x) => {
      Object.keys(this.coordinateTable[x]).forEach((y) => {
        this.baseCoordinateTable[x][y] += this.settings.pointAnimationSpeed / 1000;

        const changeX = Math.cos(this.baseCoordinateTable[x][y]) * this.settings.pointVariationX;
        const changeY = Math.sin(this.baseCoordinateTable[x][y]) * this.settings.pointVariationY;

        this.coordinateTable[x][y] = {
          x: changeX,
          y: changeY
        };
      });
    });
  }

  resize() {
    const p = this.canvas.parentElement;
    this.canvas.width = p.clientWidth;
    this.canvas.height = p.clientHeight;
    this.width = p.clientWidth;
    this.height = p.clientHeight;

    this.generateTriangles();
    this.generateParticles();
  }
}

class Particle {
  constructor(pSet) {
    this.ctx = pSet.ctx;
    this.x = getRandomInt(0, pSet.width);
    this.y = getRandomInt(0, pSet.height);
    this.ox = this.x;
    this.oy = this.y;

    this.interval = getRandomInt(1000, 5000);
    this.limit = getRandomInt(5, 15);
    this.opacity = getRandomFloat(0.1, 0.7);
    this.r = getRandomFloat(1, 2);
  }

  update() {
    this.x = this.ox + (Math.cos(performance.now() / this.interval) * this.limit);
    this.y = this.oy + ((Math.sin(performance.now() / this.interval) * this.limit) / 2);
  }

  draw() {
    this.ctx.beginPath();
    this.ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
    this.ctx.fillStyle = `rgba(255, 255, 255, ${this.opacity})`;
    this.ctx.fill();
  }
}

function gradient(coords, width, height, leftColor, rightColor) {
  const x = coords.x;
  const y = coords.y;

  let per = x / width;
  let per2 = y / height;

  per = (per2 + per) / 2;

  if (per > 1) {
    per = 1;
  } else if (per < 0) {
    per = 0;
  }

  const hex = function (x) {
    const base = x.toString(16);
    return (base.length === 1) ? `0${base}` : base;
  };

  const r = Math.ceil(parseInt(leftColor.substring(1, 3), 16) * per + parseInt(rightColor.substring(1, 3), 16) * (1 - per));
  const g = Math.ceil(parseInt(leftColor.substring(3, 5), 16) * per + parseInt(rightColor.substring(3, 5), 16) * (1 - per));
  const b = Math.ceil(parseInt(leftColor.substring(5, 7), 16) * per + parseInt(rightColor.substring(5, 7), 16) * (1 - per));

  return `#${hex(r)}${hex(g)}${hex(b)}`;
}

function getCenter(coords) {
  let sumX = 0;
  let sumY = 0;

  coords.forEach((p) => {
    sumX += p.x;
    sumY += p.y;
  });

  return { x: sumX / coords.length, y: sumY / coords.length };
}

function extend(out) {
  const output = out || {};

  for (let i = 1; i < arguments.length; i++) {
    if (!arguments[i]) {
      continue;
    }

    for (const key in arguments[i]) {
      if (Object.prototype.hasOwnProperty.call(arguments[i], key)) {
        output[key] = arguments[i][key];
      }
    }
  }

  return output;
}

function getRandomInt(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

function getRandomFloat(min, max) {
  return (Math.random() * (max - min) + min);
}

const canvas = document.getElementById('canvas');
const spaceboi = new Thpace(canvas);
spaceboi.start();

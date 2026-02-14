<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Folium Branch Study</title>
  <style>
    * { box-sizing: border-box; }

    html, body {
      margin: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
    }

    .layout {
      display: grid;
      grid-template-columns: 1fr 1fr;
      width: 100%;
      height: 100%;
      gap: 0;
    }

    .scene {
      position: relative;
      width: 100%;
      height: 100%;
      overflow: hidden;
    }

    canvas {
      display: block;
      margin: 0 auto;
    }
  </style>
</head>
<body>
  <div class="layout">
    <div class="scene">
      <canvas id="branchCanvasA" aria-label="Branch rendering A"></canvas>
    </div>
    <div class="scene">
      <canvas id="branchCanvasB" aria-label="Branch rendering B"></canvas>
    </div>
  </div>
  <script>
    window.BRANCH_SCENE_CONFIGS = [
      {
        sceneWidth: 600,
        sceneHeight: 300,
        rotationDeg: 90,
        trunkWaviness: 0,
        branchWaviness: 1,
        branches: [
          { percent: 0.2, direction: "left", lengthFactor: 1.38, waviness: 1.1 },
          { percent: 0.62, direction: "right", lengthFactor: 1.46, waviness: 0.9 }
        ]
      },
      {
        sceneWidth: 300,
        sceneHeight: 600,
        rotationDeg: 0,
        trunkWaviness: 0.8,
        branchWaviness: 1.2,
        branches: [
          { percent: 0.28, direction: "left", lengthFactor: 1.2, waviness: 1.4 },
          { percent: 0.7, direction: "right", lengthFactor: 1.35, waviness: 0.7 }
        ]
      }
    ];
  </script>
  <script src="./index.js"></script>
  <script>
    (function () {
      const canvases = [
        document.getElementById("branchCanvasA"),
        document.getElementById("branchCanvasB")
      ];
      const configs = window.BRANCH_SCENE_CONFIGS || [];
      if (window.BranchSceneLibrary) {
        window.branchScenes = canvases.map(function (canvas, idx) {
          if (!canvas) return null;
          return window.BranchSceneLibrary.mount(canvas, configs[idx] || {});
        }).filter(Boolean);

        window.setTrunkLeafMaxPercent = function (percent) {
          for (let i = 0; i < window.branchScenes.length; i++) {
            const scene = window.branchScenes[i];
            if (scene && scene.setTrunkLeafMaxPercent) {
              scene.setTrunkLeafMaxPercent(percent);
            }
          }
        };
      }
    })();
  </script>
</body>
</html>

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

    .scene {
      position: relative;
      width: 100%;
      height: 100%;
    }

    canvas {
      display: block;
      margin: 0 auto;
    }
  </style>
</head>
<body>
  <div class="scene">
    <canvas id="branchCanvas" aria-label="Animated branch rendering"></canvas>
  </div>
  <script>
    window.BRANCH_SCENE_CONFIG = {
      rotationDeg: 0,
      branches: [
        { percent: 0.2, direction: "left", lengthFactor: 1.38 },
        // { percent: 0.62, direction: "right", lengthFactor: 1.46 }
      ]
    };
  </script>
  <script src="./index.js"></script>
  <script>
    (function () {
      const canvas = document.getElementById("branchCanvas");
      const cfg = window.BRANCH_SCENE_CONFIG || {};
      if (window.BranchSceneLibrary && canvas) {
        window.branchScene = window.BranchSceneLibrary.mount(canvas, cfg);
        if (window.branchScene && window.branchScene.setTrunkLeafMaxPercent) {
          window.setTrunkLeafMaxPercent = window.branchScene.setTrunkLeafMaxPercent;
        }
      }
    })();
  </script>
</body>
</html>

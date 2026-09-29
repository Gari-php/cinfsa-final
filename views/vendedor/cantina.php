<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Cantina</title>
 
  <link href="https://fonts.googleapis.com/css2?family=Luckiest+Guy&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Arial', sans-serif;
      background-color: #363130;
      color: #000;
    }

    header {
      background-color: #363130;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 20px;
    }

    .logo {
      font-size: 1.5rem;
      font-weight: bold;
    }

    h1 {
      font-family: 'Luckiest Guy', cursive;
      font-size: 2.5rem;
      color: #ed850f;
    }

    .contacto {
      display: flex;
      flex-direction: column;
      font-size: 0.9rem;
      text-align: right;
    }

    nav { 
      background-color: #ed850f;
      text-align: center;
      padding: 10px;
    }

    	.boton {
    	color: white; 
    	text-decoration: none; 
    	background-color: #363130; 
    	padding: 10px 20px; 
    	border-radius: 5px; 
    	font-size: 1.1em; 
    	font-weight: bold; 
    	display: inline-block; 
    	transition: background-color 0.3s ease; 
  }

	.boton:hover {
    	background-color: gray; 
    	text-decoration: underline; 
  }

    main {
      padding: 20px;
    }

    .productos {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(min(220px, 100%), 1fr));
      gap: 20px;
      text-align: center;
    }

    .producto {
      background-color: white;
      border-radius: 15px;
      padding: 15px;
      box-shadow: 0 0 10px #00000033;
    }

    .producto h3 {
      font-size: 1.1rem;
      margin-bottom: 10px;
    }

    .producto img {
      width: 100px;
      height: auto;
      margin-bottom: 10px;
    }

    .acciones {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      margin: 10px 0;
    }

    .acciones button {
      background-color: #2f52ff;
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 50%;
      cursor: pointer;
      font-size: 1.1rem;
      width: 30px;
      height: 30px;
    }

    .acciones span {
      font-weight: bold;
      font-size: 1.1rem;
      min-width: 20px;
      display: inline-block;
    }

    .agregar {
      background-color: #00cc66;
      color: white;
      padding: 5px 20px;
      border: none;
      border-radius: 10px;
      font-weight: bold;
      cursor: pointer;
      margin-top: 5px;
    }

    .precio {
      display: block;
      font-weight: bold;
      margin: 5px 0;
      color: #000;
    }

    @media (max-width: 600px) {
      header {
        flex-direction: column;
        gap: 6px;
        text-align: center;
      }

      h1 {
        font-size: 2rem;
        margin: 4px 0;
      }

      .contacto {
        text-align: center;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="logo">🎟️ CINFSA</div>
    <h1>CANTINA</h1>
    <div class="contacto">
      <span>📷 @cinfsa</span>
      <span>📞 3704423459</span>
      <span>📧 cinfsa@gmail.com</span>
    </div>
  </header>

  <nav>
    <a href="/vendedor" class="boton">Volver</a>
  </nav>

  <main>
    <section class="productos">
      <div class="producto">
        <h3>Pochoclos Chicos</h3>
        <img src="../../assets/img/pochoclos.jpeg" alt="Pochoclo Chico"/>
        <span class="precio">$2,000</span>
        <div class="acciones">
          <button>-</button>
          <span>0</span>
          <button>+</button>
        </div>
        <button class="agregar">agregar</button>
      </div>

      <div class="producto">
        <h3>Pochoclos Medianos</h3>
        <img src="../../assets/img/pochoclos.jpeg" alt="Pochoclo Mediano"/>
        <span class="precio">$3,000</span>
        <div class="acciones">
          <button>-</button>
          <span>2</span>
          <button>+</button>
        </div>
        <button class="agregar">agregar</button>
      </div>

      <div class="producto">
        <h3>Pochoclos Grandes</h3>
        <img src="../../assets/img/pochoclos.jpeg" alt="Pochoclo Grande"/>
        <span class="precio">$4,000</span>
        <div class="acciones">
          <button>-</button>
          <span>0</span>
          <button>+</button>
        </div>
        <button class="agregar">agregar</button>
      </div>

      <div class="producto">
        <h3>Coca-Cola chica 250ml</h3>
        <img src="../../assets/img/coca.jpeg" alt="Coca Cola"/>
        <span class="precio">$2,000</span>
        <div class="acciones">
          <button>-</button>
          <span>0</span>
          <button>+</button>
        </div>
          <button class="agregar">agregar</button>
      </div>

      <div class="producto">
        <h3>Fanta Chica 250ml</h3>
        <img src="../../assets/img/fanta.jpeg" alt="Fanta"/>
        <span class="precio">$2,000</span>
        <div class="acciones">
          <button>-</button>
          <span>0</span>
          <button>+</button>
        </div>
        <button class="agregar">agregar</button>
      </div>

      <div class="producto">
        <h3>Sprite Chica 250ml</h3>
        <img src="../../assets/img/sprite.jpeg" alt="Sprite"/>
        <span class="precio">$2,000</span>
        <div class="acciones">
          <button>-</button>
          <span>0</span>
          <button>+</button>
        </div>
         <button class="agregar">agregar</button>
      </div>
    </section>
  </main>
</body>
</html>
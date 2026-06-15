import express from "express";
import mysql from "mysql2/promise"; // Використовуємо проміси для асинхронного коду
import cors from "cors";

const app = express();

// ✅ Налаштування CORS (дозволяємо запити з будь-якого хостингу для тестів, або впишіть свій GitHub Pages)
app.use(cors({ 
  origin: "*" 
}));

app.use(express.json());

// ✅ Підключення до MySQL через внутрішню або зовнішню змінну Railway
// Якщо змінна DATABASE_URL порожня, додаток спробує локальне підключення
const dbUrl = process.env.DATABASE_URL || "mysql://root@localhost:3306/railway";

let pool;
try {
  pool = mysql.createPool(dbUrl);
  console.log("MySQL connection pool created successfully");
} catch (err) {
  console.error("Failed to create MySQL connection pool:", err.message);
}

// 📦 API маршрут для отримання всіх продуктів
app.get("/products", async (req, res) => {
  try {
    // Робимо запит до таблиці products, яку ми завантажили через DBeaver
    const [rows] = await pool.query("SELECT * FROM `products`");
    res.json(rows);
  } catch (err) {
    console.error("Error executing query:", err.message);
    res.status(500).json({ error: "Internal Server Error", details: err.message });
  }
});

// 🚀 Запуск сервера на порті, який вимагає хостинг, або на замовчуванням 3000
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));

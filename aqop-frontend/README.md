# AQOP Platform - React Frontend

Enterprise-grade Operations Platform frontend built with React, Vite, and Tailwind CSS.

## 🚀 Quick Start

### Prerequisites

- Node.js 18+ 
- npm or yarn
- WordPress backend running at `http://localhost:8888/aqleeat-operation`

### Installation

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Create environment file:**
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` and set your API URL:
   ```
   VITE_API_URL=http://localhost:8888/aqleeat-operation/wp-json
   ```

3. **Start development server:**
   ```bash
   npm run dev
   ```

   The app will run at `http://localhost:5173`

## 📁 Project Structure

```
src/
├── api/              # API client and endpoint definitions
│   ├── index.js      # Base API client
│   └── auth.js       # Authentication API calls
├── auth/             # Authentication context and components
│   ├── AuthContext.jsx    # Auth state management
│   └── ProtectedRoute.jsx # Route protection wrapper
├── components/       # Reusable UI components
│   └── LoadingSpinner.jsx
├── pages/            # Page components
│   ├── LoginPage.jsx
│   └── DashboardPage.jsx
├── hooks/            # Custom React hooks
│   └── useAuth.js
├── utils/            # Utility functions and constants
│   ├── constants.js
│   └── helpers.js
├── App.jsx           # Main app component
├── main.jsx          # App entry point
└── index.css         # Global styles with Tailwind
```

## 🔐 Authentication

The app uses JWT authentication with the following flow:

1. User logs in with username/password
2. Backend returns access token (15 min) and refresh token (7 days)
3. Tokens are stored in localStorage
4. Protected routes check authentication status
5. API client automatically adds Bearer token to requests

### Login

Navigate to `/login` and enter credentials:
- Username: your WordPress username
- Password: your WordPress password

Only users with these roles can log in:
- `administrator`
- `operation_admin`
- `operation_manager`
- `aq_supervisor`
- `aq_agent`

## 🎨 Styling

The app uses Tailwind CSS for styling. Key features:

- Responsive design
- Modern UI components
- Utility-first approach
- Easy customization via `tailwind.config.js`

## 📡 API Integration

### Base Configuration

API client is configured in `src/api/index.js`:
- Base URL from environment variable
- Automatic authentication header injection
- Error handling
- Support for GET, POST, PUT, DELETE

### Making API Calls

```javascript
import apiClient from './api';

// GET request
const data = await apiClient.get('/endpoint');

// POST request
const result = await apiClient.post('/endpoint', { data });
```

### Authentication API

```javascript
import { login, logout, refreshToken } from './api/auth';

// Login
const response = await login('username', 'password');

// Logout
await logout();

// Refresh token
await refreshToken();
```

## 🛣️ Routing

Routes are defined in `src/main.jsx`:

- `/login` - Login page (public)
- `/dashboard` - Dashboard (protected)
- `/` - Redirects to dashboard

### Adding Protected Routes

```javascript
<Route
  path="/new-page"
  element={
    <ProtectedRoute>
      <NewPage />
    </ProtectedRoute>
  }
/>
```

## 🔧 Available Scripts

- `npm run dev` - Start development server
- `npm run build` - Build for production
- `npm run preview` - Preview production build
- `npm run lint` - Run ESLint

## 🌐 Environment Variables

Create a `.env` file in the root directory:

```env
VITE_API_URL=http://localhost:8888/aqleeat-operation/wp-json
```

Access in code:
```javascript
const apiUrl = import.meta.env.VITE_API_URL;
```

## 🔒 Security

- JWT tokens with expiration
- Token refresh mechanism
- Protected routes
- CORS configured for localhost:5173
- Automatic token cleanup on logout

## 🚧 Development Notes

### Adding New Pages

1. Create page component in `src/pages/`
2. Add route in `src/main.jsx`
3. Wrap with `ProtectedRoute` if authentication required

### Adding New API Endpoints

1. Add endpoint definition in `src/api/`
2. Use `apiClient` for requests
3. Handle errors appropriately

### Customizing Theme

Edit `tailwind.config.js` to customize:
- Colors
- Fonts
- Spacing
- Breakpoints

## 📦 Dependencies

### Production
- `react` - UI library
- `react-dom` - React DOM rendering
- `react-router-dom` - Routing

### Development
- `vite` - Build tool
- `tailwindcss` - CSS framework
- `postcss` - CSS processing
- `autoprefixer` - CSS vendor prefixes
- `eslint` - Code linting

## 🐛 Troubleshooting

### CORS Errors

Ensure the WordPress backend has CORS headers configured for `http://localhost:5173`

### Authentication Fails

1. Check that JWT plugin is activated in WordPress
2. Verify API URL in `.env` file
3. Check browser console for error messages
4. Verify user has correct role

### Tailwind Styles Not Working

1. Ensure Tailwind is installed: `npm install -D tailwindcss postcss autoprefixer`
2. Check `tailwind.config.js` exists
3. Verify `postcss.config.js` exists
4. Restart dev server

## 📝 License

GPL v2 or later

## 👤 Author

Muhammed Derviş - AQOP Platform

---

For more information, visit [https://aqleeat.com](https://aqleeat.com)

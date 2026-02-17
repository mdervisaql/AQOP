# AQOP Frontend Setup Guide

## ✅ What Has Been Created

### Folder Structure
```
src/
├── api/                    ✅ Created
│   ├── index.js           - Base API client with authentication
│   └── auth.js            - Authentication API endpoints
├── auth/                   ✅ Created
│   ├── AuthContext.jsx    - Global auth state management
│   └── ProtectedRoute.jsx - Route protection wrapper
├── components/             ✅ Created
│   └── LoadingSpinner.jsx - Reusable loading component
├── pages/                  ✅ Created
│   ├── LoginPage.jsx      - Login page with form
│   └── DashboardPage.jsx  - Protected dashboard
├── hooks/                  ✅ Created
│   └── useAuth.js         - Auth hook export
├── utils/                  ✅ Created
│   ├── constants.js       - App constants (roles, routes, etc)
│   └── helpers.js         - Utility functions
├── main.jsx               ✅ Updated - Routing configured
└── index.css              ✅ Updated - Tailwind directives added
```

### Configuration Files
- ✅ `tailwind.config.js` - Tailwind CSS configuration
- ✅ `postcss.config.js` - PostCSS configuration
- ✅ `package.json` - Updated with all dependencies
- ✅ `.gitignore` - Updated with .env exclusion
- ✅ `README.md` - Complete documentation

### Removed
- ❌ `src/App.css` - Deleted (using Tailwind)
- ❌ `src/App.jsx` - Deleted (routing in main.jsx)

---

## 🚀 Required Setup Steps

### Step 1: Create .env File

Create a file named `.env` in the frontend root directory:

```bash
cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation/aqop-frontend
echo "VITE_API_URL=http://localhost:8888/aqleeat-operation/wp-json" > .env
```

Or create it manually with this content:
```
VITE_API_URL=http://localhost:8888/aqleeat-operation/wp-json
```

### Step 2: Install Dependencies

```bash
cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation/aqop-frontend
npm install
```

This will install:
- ✅ React 19.2.0
- ✅ React DOM 19.2.0
- ✅ React Router DOM 6.28.0
- ✅ Tailwind CSS 3.4.17
- ✅ PostCSS 8.4.49
- ✅ Autoprefixer 10.4.20
- ✅ Vite 7.2.2
- ✅ ESLint and plugins

### Step 3: Start Development Server

```bash
npm run dev
```

The app will start at: `http://localhost:5173`

---

## 🧪 Test the Application

### 1. Check if Server Starts
- Navigate to `http://localhost:5173`
- Should see the login page

### 2. Test Login
- Enter WordPress credentials
- Click "Sign in"
- Should redirect to dashboard on success

### 3. Test Protected Routes
- Try accessing `/dashboard` without logging in
- Should redirect to `/login`

### 4. Test Logout
- Click "Logout" button in dashboard
- Should redirect to login page
- Token should be removed from localStorage

---

## 🔧 Troubleshooting

### Issue: Tailwind styles not working

**Solution:**
```bash
npm install -D tailwindcss postcss autoprefixer
npm run dev
```

### Issue: CORS errors in console

**Solution:**
1. Activate JWT plugin in WordPress
2. Check that CORS headers are set for `http://localhost:5173`
3. Verify in JWT plugin file: `aqop-jwt-auth/aqop-jwt-auth.php`

### Issue: Authentication fails

**Check:**
1. JWT plugin activated in WordPress?
2. `.env` file exists with correct API URL?
3. Backend running at `http://localhost:8888/aqleeat-operation`?
4. User has correct role? (admin, operation_admin, etc.)

### Issue: Module not found errors

**Solution:**
```bash
rm -rf node_modules package-lock.json
npm install
```

---

## 📡 API Endpoints

Your app will connect to these WordPress REST API endpoints:

### Authentication
- `POST /aqop-jwt/v1/login` - Login and get tokens
- `POST /aqop-jwt/v1/refresh` - Refresh access token
- `POST /aqop-jwt/v1/logout` - Logout and blacklist token
- `POST /aqop-jwt/v1/validate` - Validate token

### Future Endpoints (to be added)
- `/aqop-leads/v1/leads` - Leads CRUD
- `/aqop-core/v1/users` - User management
- `/aqop-core/v1/events` - Event logs

---

## 🎨 Tailwind CSS

### Using Tailwind in Components

```jsx
// Example component
export default function MyComponent() {
  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h1 className="text-3xl font-bold text-gray-900">
        Title
      </h1>
      <p className="text-gray-600 mt-2">
        Description
      </p>
    </div>
  );
}
```

### Customizing Theme

Edit `tailwind.config.js`:

```javascript
export default {
  theme: {
    extend: {
      colors: {
        primary: '#your-color',
      },
    },
  },
}
```

---

## 🔐 Authentication Flow

1. **User enters credentials** on `/login`
2. **API call** to `/aqop-jwt/v1/login`
3. **Backend validates** and returns:
   - Access token (15 min expiry)
   - Refresh token (7 days expiry)
   - User data
4. **Tokens stored** in localStorage
5. **User redirected** to `/dashboard`
6. **All API calls** include Bearer token automatically
7. **On logout**, token blacklisted and removed

---

## 📦 Package.json Scripts

```bash
npm run dev      # Start development server
npm run build    # Build for production
npm run preview  # Preview production build
npm run lint     # Run ESLint
```

---

## 🔄 Next Steps

### Immediate
1. ✅ Create .env file
2. ✅ Run `npm install`
3. ✅ Start dev server with `npm run dev`
4. ✅ Test login with WordPress credentials

### Phase 2 - Leads Module
- Create leads list page
- Create lead detail page
- Add lead forms
- Implement filters and search

### Phase 3 - Dashboard Enhancement
- Add real statistics
- Create charts/graphs
- Add notifications
- Recent activity feed

### Phase 4 - User Management
- User list page
- User roles management
- Permission settings

---

## ✅ Verification Checklist

Before proceeding, verify:

- [ ] `.env` file exists with correct API URL
- [ ] `npm install` completed successfully
- [ ] Dev server starts without errors
- [ ] Can access `http://localhost:5173`
- [ ] Login page displays correctly
- [ ] Tailwind styles are working (blue login button, etc.)
- [ ] JWT plugin activated in WordPress backend
- [ ] Backend running at configured URL

---

## 🆘 Getting Help

If you encounter issues:

1. Check browser console for errors
2. Check terminal for build errors
3. Verify .env file is correct
4. Ensure WordPress backend is running
5. Check that JWT plugin is activated

---

## 📝 Important Notes

- ⚠️ `.env` file is gitignored - don't commit it
- ⚠️ Tokens stored in localStorage (consider more secure storage for production)
- ⚠️ CORS is configured for localhost only
- ⚠️ For production, update CORS settings and API URL

---

**Ready to proceed with Step 1: Create .env file**

Good luck! 🚀


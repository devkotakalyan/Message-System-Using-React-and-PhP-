import { Link, Navigate, Route, Routes, useLocation } from "react-router-dom";
import Login from "./pages/Login.jsx";
import Register from "./pages/Register.jsx";
import Friends from "./pages/Friends.jsx";
import Chat from "./pages/Chat.jsx";
import { useAuth } from "./auth/AuthContext.jsx";

function PrivateRoute({ children }) {
  const { token } = useAuth();
  const location = useLocation();
  if (!token) {
    return <Navigate to="/login" replace state={{ from: location }} />;
  }
  return children;
}

export default function App() {
  const { token, user, logout } = useAuth();
  return (
    <div className="container">
      <nav className="nav">
        <div className="brand">Message System</div>
        <div className="links">
          {token ? (
            <>
              <Link to="/friends">Friends</Link>
              <span className="spacer" />
              <span>Hello, {user?.name}</span>
              <button className="btn" onClick={logout}>
                Logout
              </button>
            </>
          ) : (
            <>
              <Link to="/login">Login</Link>
              <Link to="/register">Register</Link>
            </>
          )}
        </div>
      </nav>
      <main className="main">
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route
            path="/friends"
            element={
              <PrivateRoute>
                <Friends />
              </PrivateRoute>
            }
          />
          <Route
            path="/chat/:peerId"
            element={
              <PrivateRoute>
                <Chat />
              </PrivateRoute>
            }
          />
          <Route
            path="*"
            element={<Navigate to={token ? "/friends" : "/login"} replace />}
          />
        </Routes>
      </main>
    </div>
  );
}

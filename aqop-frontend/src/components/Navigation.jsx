import { useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import {
  LayoutDashboard,
  Users,
  BarChart3,
  FileText,
  LogOut,
  Shield,
  Activity,
  Settings,
  Menu,
  X,
  Facebook,
  MessageSquare,
  PieChart,
  BookOpen,
  HelpCircle,
  Target
} from 'lucide-react';
import { useState, useEffect } from 'react';

/**
 * Unified Navigation Sidebar Component
 *
 * Features:
 * - Dark Sidebar Layout
 * - Responsive Mobile Menu
 * - Role-based Navigation
 * - User Profile Section
 */
export default function Navigation({ currentPage = '' }) {
  const navigate = useNavigate();
  const { user, logout } = useAuth();
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  // Close sidebar on escape key
  useEffect(() => {
    const handleEscape = (e) => {
      if (e.key === 'Escape' && isMobileMenuOpen) {
        setIsMobileMenuOpen(false);
      }
    };
    document.addEventListener('keydown', handleEscape);
    return () => document.removeEventListener('keydown', handleEscape);
  }, [isMobileMenuOpen]);

  // Prevent body scroll when sidebar is open
  useEffect(() => {
    if (isMobileMenuOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [isMobileMenuOpen]);

  // Role checking
  const isAgent = user?.role === 'aq_agent';
  const isSupervisor = user?.role === 'aq_supervisor';
  const isManager = ['administrator', 'operation_admin', 'operation_manager', 'aq_country_manager'].includes(user?.role);
  const isAdmin = ['administrator', 'operation_admin'].includes(user?.role);
  const isDigitalMarketing = ['administrator', 'operation_admin', 'digital_marketing'].includes(user?.role);

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const NavItem = ({ to, icon: Icon, label, activeId }) => {
    const isActive = currentPage === activeId;
    return (
      <button
        onClick={() => {
          navigate(to);
          setIsMobileMenuOpen(false);
        }}
        className={`w-full flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors ${isActive
          ? 'bg-indigo-600 text-white'
          : 'text-slate-300 hover:bg-slate-800 hover:text-white'
          }`}
      >
        <Icon size={20} />
        <span className="font-medium">{label}</span>
      </button>
    );
  };

  const SidebarContent = () => (
    <div className="flex flex-col h-full">
      {/* Brand */}
      <div className="flex items-center space-x-3 px-6 py-6 border-b border-slate-800">
        <div className="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center">
          <span className="text-white font-bold text-xl">A</span>
        </div>
        <span className="text-white font-bold text-xl tracking-tight">AQOP Platform</span>
      </div>

      {/* Navigation Links */}
      <div className="flex-1 overflow-y-auto py-6 px-3 space-y-1">
        {isAgent && (
          <NavItem to="/leads" icon={FileText} label="My Leads" activeId="my-leads" />
        )}

        {isSupervisor && (
          <NavItem to="/supervisor/team-leads" icon={Users} label="Team Leads" activeId="team-leads" />
        )}

        {isManager && (
          <>
            <div className="px-4 py-2 mt-4 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
              Management
            </div>
            <NavItem to="/manager/all-leads" icon={LayoutDashboard} label="All Leads" activeId="all-leads" />
            <NavItem to="/manager/bulk-whatsapp" icon={MessageSquare} label="Bulk WhatsApp" activeId="bulk-whatsapp" />
            <NavItem to="/manager/reports" icon={PieChart} label="Reports" activeId="reports" />
            <NavItem to="/manager/analytics" icon={BarChart3} label="Analytics" activeId="analytics" />
          </>
        )}

        {isAdmin && (
          <>
            <div className="px-4 py-2 mt-4 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
              Administration
            </div>
            <NavItem to="/admin/users" icon={Users} label="User Management" activeId="users" />
            <NavItem to="/settings/learning-paths" icon={BookOpen} label="Learning Paths" activeId="learning-paths" />
            <NavItem to="/settings/faq" icon={HelpCircle} label="FAQ Management" activeId="faq" />
            <NavItem to="/settings/conversion-targets" icon={Target} label="Conversion Targets" activeId="conversion-targets" />
            <NavItem to="/system-health" icon={Activity} label="System Health" activeId="system-health" />
          </>
        )}

        {isDigitalMarketing && (
          <>
            <div className="px-4 py-2 mt-4 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
              Marketing
            </div>
            <NavItem to="/settings/facebook-leads" icon={Facebook} label="Facebook Leads" activeId="facebook-leads" />
          </>
        )}
      </div>

      {/* Settings Link */}
      <div className="px-3 pb-2">
        <NavItem to="/settings/notifications" icon={Settings} label="Settings" activeId="settings" />
      </div>

      {/* User Profile & Logout */}
      <div className="p-4 border-t border-slate-800 bg-slate-900/50">
        <div className="flex items-center space-x-3 mb-4 px-2">
          <div className="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-slate-300 font-medium">
            {user?.display_name?.charAt(0).toUpperCase() || 'U'}
          </div>
          <div className="flex-1 min-w-0">
            <p className="text-sm font-medium text-white truncate">
              {user?.display_name || user?.username}
            </p>
            <p className="text-xs text-slate-400 truncate">
              {user?.role?.replace(/_/g, ' ').replace('aq ', '')}
            </p>
          </div>
        </div>
        <button
          onClick={handleLogout}
          className="w-full flex items-center justify-center space-x-2 px-4 py-2 bg-slate-800 hover:bg-rose-600 text-slate-300 hover:text-white rounded-lg transition-colors text-sm font-medium"
        >
          <LogOut size={16} />
          <span>Sign Out</span>
        </button>
      </div>
    </div>
  );

  return (
    <>
      {/* Mobile Header - Fixed at top, only on mobile */}
      <div className="lg:hidden fixed top-0 left-0 right-0 h-14 bg-slate-900 text-white px-4 flex justify-between items-center z-40">
        <button
          onClick={() => setIsMobileMenuOpen(true)}
          className="p-2 -ml-2"
          aria-label="Open menu"
        >
          <Menu size={24} />
        </button>
        <span className="font-bold text-lg">AQOP Platform</span>
        <div className="w-10"></div> {/* Spacer for centering */}
      </div>

      {/* Mobile Sidebar Overlay */}
      {isMobileMenuOpen && (
        <div
          className="fixed inset-0 bg-black/50 z-40 lg:hidden backdrop-blur-sm"
          onClick={() => setIsMobileMenuOpen(false)}
        />
      )}

      {/* Sidebar Container */}
      <aside className={`
        fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300
        transform transition-transform duration-300 ease-in-out
        shadow-2xl
        
        /* MOBILE: Hidden by default, slide in when open */
        ${isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'}
        
        /* DESKTOP: Always visible, static positioning */
        lg:translate-x-0 lg:static lg:shadow-none lg:flex lg:flex-col lg:h-screen
      `}>
        {/* Close button for mobile */}
        <button
          onClick={() => setIsMobileMenuOpen(false)}
          className="lg:hidden absolute top-4 right-4 text-slate-400 hover:text-white p-2 z-50"
          aria-label="Close menu"
        >
          <X size={20} />
        </button>
        <SidebarContent />
      </aside>
    </>
  );
}


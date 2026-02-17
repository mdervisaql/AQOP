import { NavLink, useLocation } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import { Home, Users, BarChart3, User, Bell } from 'lucide-react';

/**
 * Bottom Navigation Component (Mobile Only)
 * 
 * Role-based navigation with active state highlighting.
 * Hidden on desktop (lg:hidden).
 */
export default function BottomNav() {
    const { user } = useAuth();
    const location = useLocation();

    // Role checking
    const isAgent = user?.role === 'aq_agent';
    const isSupervisor = user?.role === 'aq_supervisor';
    const isManager = ['administrator', 'operation_admin', 'operation_manager', 'aq_country_manager'].includes(user?.role);

    // Build nav items based on role
    const getNavItems = () => {
        const items = [
            {
                path: '/dashboard',
                label: 'الرئيسية',
                labelEn: 'Home',
                icon: Home,
            },
        ];

        // Agent: My Leads
        if (isAgent) {
            items.push({
                path: '/leads',
                label: 'ليداتي',
                labelEn: 'My Leads',
                icon: Users,
            });
        }

        // Supervisor: Team Leads
        if (isSupervisor || isManager) {
            items.push({
                path: isSupervisor ? '/supervisor/team-leads' : '/manager/all-leads',
                label: isSupervisor ? 'الفريق' : 'الليدات',
                labelEn: isSupervisor ? 'Team' : 'All Leads',
                icon: Users,
            });
        }

        // Manager: Analytics
        if (isManager) {
            items.push({
                path: '/manager/analytics',
                label: 'التحليلات',
                labelEn: 'Analytics',
                icon: BarChart3,
            });
        }

        // Notifications
        items.push({
            path: '/notifications',
            label: 'الإشعارات',
            labelEn: 'Alerts',
            icon: Bell,
        });

        // Profile always last
        items.push({
            path: '/profile',
            label: 'حسابي',
            labelEn: 'Profile',
            icon: User,
        });

        // Limit to 5 items max for mobile
        return items.slice(0, 5);
    };

    const navItems = getNavItems();

    return (
        <nav className="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-30 safe-area-bottom">
            <div className="flex justify-around items-center h-16">
                {navItems.map((item) => {
                    const Icon = item.icon;
                    const isActive = location.pathname === item.path ||
                        (item.path !== '/dashboard' && location.pathname.startsWith(item.path));

                    return (
                        <NavLink
                            key={item.path}
                            to={item.path}
                            className={`
                flex flex-col items-center justify-center flex-1 h-full
                transition-colors duration-200 touch-manipulation
                ${isActive
                                    ? 'text-blue-600'
                                    : 'text-gray-500 active:text-gray-700'
                                }
              `}
                        >
                            <Icon className="w-6 h-6" strokeWidth={isActive ? 2.5 : 2} />
                            <span className="text-xs mt-1 font-medium">{item.label}</span>
                        </NavLink>
                    );
                })}
            </div>
        </nav>
    );
}

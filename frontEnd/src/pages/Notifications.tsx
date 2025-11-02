import React, { useEffect, useState } from 'react';
import { Layout } from '../components/layout/Layout';
import { Card } from '../components/common/Card';
import api from '../services/api';
import toast from 'react-hot-toast';
import { Notification } from '../types';

type FilterType = 'all' | 'unread' | 'read';

export const Notifications: React.FC = () => {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [filter, setFilter] = useState<FilterType>('all');

  const fetchNotifications = async (): Promise<void> => {
    setLoading(true);
    try {
      const response = await api.get<Notification[]>('/notifications');
      setNotifications(response.data);
    } catch (error) {
      toast.error('Erreur lors du chargement');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchNotifications();
  }, []);

  const handleMarkAsRead = async (id: number): Promise<void> => {
    try {
      await api.post(`/notifications/${id}/mark-as-read`);
      fetchNotifications();
      toast.success('Notification marquée comme lue');
    } catch (error) {
      toast.error('Erreur');
    }
  };

  const handleMarkAllAsRead = async (): Promise<void> => {
    try {
      await api.post('/notifications/mark-all-as-read');
      fetchNotifications();
      toast.success('Toutes les notifications ont été marquées comme lues');
    } catch (error) {
      toast.error('Erreur');
    }
  };

  const filteredNotifications = notifications.filter(n => {
    if (filter === 'unread') return !n.isRead;
    if (filter === 'read') return n.isRead;
    return true;
  });

  const getNotificationIcon = (type: Notification['type']): { icon: string; color: string } => {
    switch (type) {
      case 'treatment_validated':
        return { icon: '✅', color: 'bg-green-100 text-green-600' };
      
      case 'treatment_to_modify':
        return { icon: '✏️', color: 'bg-yellow-100 text-yellow-600' };
      case 'treatment_submitted':
        return { icon: '🔔', color: 'bg-blue-100 text-blue-600' };
      default:
        return { icon: '📢', color: 'bg-gray-100 text-gray-600' };
    }
  };

  const handleNotificationClick = (notification: Notification): void => {
    if (!notification.isRead) {
      handleMarkAsRead(notification.id);
    }
    if (notification.data?.action === 'view' || notification.data?.action === 'edit') {
      window.location.href = `/treatments/${notification.data.treatmentId}`;
    }
  };

  return (
    <Layout>
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex items-center justify-between mb-8">
          <h1 className="text-3xl font-bold text-gray-900">Notifications</h1>
          {notifications.some(n => !n.isRead) && (
            <button
              onClick={handleMarkAllAsRead}
              className="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-800"
            >
              Tout marquer comme lu
            </button>
          )}
        </div>

        <div className="flex space-x-2 mb-6">
          <button
            onClick={() => setFilter('all')}
            className={`px-4 py-2 rounded-lg text-sm font-medium ${
              filter === 'all'
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            Toutes ({notifications.length})
          </button>
          <button
            onClick={() => setFilter('unread')}
            className={`px-4 py-2 rounded-lg text-sm font-medium ${
              filter === 'unread'
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            Non lues ({notifications.filter(n => !n.isRead).length})
          </button>
          <button
            onClick={() => setFilter('read')}
            className={`px-4 py-2 rounded-lg text-sm font-medium ${
              filter === 'read'
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            Lues ({notifications.filter(n => n.isRead).length})
          </button>
        </div>

        <Card>
          {loading ? (
            <div className="text-center py-12">
              <i className="bi bi-arrow-clockwise animate-spin text-2xl text-blue-600 mr-3"></i>
              <span className="text-gray-600">Chargement...</span>
            </div>
          ) : filteredNotifications.length === 0 ? (
            <div className="text-center py-12">
              <i className="bi bi-inbox text-4xl text-gray-400 mb-4 block"></i>
              <p className="text-gray-600">
                {filter === 'unread' 
                  ? 'Aucune notification non lue' 
                  : filter === 'read'
                  ? 'Aucune notification lue'
                  : 'Aucune notification'}
              </p>
            </div>
          ) : (
            <div className="divide-y divide-gray-200">
              {filteredNotifications.map((notification) => {
                const { icon, color } = getNotificationIcon(notification.type);
                return (
                  <div
                    key={notification.id}
                    onClick={() => handleNotificationClick(notification)}
                    className={`p-4 hover:bg-gray-50 cursor-pointer transition-colors ${
                      !notification.isRead ? 'bg-blue-50' : ''
                    }`}
                  >
                    <div className="flex items-start space-x-4">
                      <div className={`w-12 h-12 rounded-full ${color} flex items-center justify-center text-2xl flex-shrink-0`}>
                        {icon}
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-start justify-between">
                          <div className="flex-1">
                            <p className={`text-base font-medium text-gray-900 ${!notification.isRead ? 'font-semibold' : ''}`}>
                              {notification.title}
                            </p>
                            <p className="text-sm text-gray-600 mt-1">
                              {notification.message}
                            </p>
                            {notification.treatment && (
                              <p className="text-sm text-blue-600 mt-2">
                                <i className="bi bi-file-text mr-1"></i>
                                {notification.treatment.nomTraitement}
                              </p>
                            )}
                            <p className="text-xs text-gray-400 mt-2">
                              {new Date(notification.createdAt).toLocaleDateString('fr-FR', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                              })}
                            </p>
                          </div>
                          {!notification.isRead && (
                            <span className="w-3 h-3 bg-blue-600 rounded-full flex-shrink-0 ml-2"></span>
                          )}
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </Card>
      </div>
    </Layout>
  );
};
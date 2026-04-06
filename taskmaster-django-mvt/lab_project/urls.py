from django.contrib import admin
from django.urls import path, include
from core import views  # <--- THIS IS THE MISSING LINE

urlpatterns = [
    path('admin/', admin.site.urls),
    path('accounts/', include('django.contrib.auth.urls')),
    path('', views.task_list, name='task_list'),
    path('add/', views.create_task, name='create_task'),
]
from django.shortcuts import render, redirect
from .models import Task
from django.contrib.auth.decorators import login_required

@login_required
def task_list(request):  # <--- Check this name!
    visits = request.session.get('visits', 0) + 1
    request.session['visits'] = visits
    
    tasks = Task.objects.filter(owner=request.user)
    return render(request, 'core/task_list.html', {'tasks': tasks, 'visits': visits})

def create_task(request): # <--- And this name!
    if request.method == 'POST':
        title = request.POST.get('title')
        description = request.POST.get('description')
        Task.objects.create(title=title, description=description, owner=request.user)
        return redirect('task_list')
    return render(request, 'core/add_task.html')
import logging

class SimpleLoggingMiddleware:
    def __init__(self, get_response):
        self.get_response = get_response

    def __call__(self, request):
        # Logic before the view: Security/Logging
        print(f"Request Path: {request.path} | User: {request.user}")
        
        response = self.get_response(request)
        
        # Logic after the view
        return response
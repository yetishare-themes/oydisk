var flowLogin = flowLogin || {};

;(function($, window, undefined)
{
	"use strict";
	
	$(document).ready(function()
	{
		flowLogin.$container = $("#form_login");
		
		// Login Form & Validation
		flowLogin.$container.validate({
			rules: {
				username: {
					required: true	
				},
				
				password: {
					required: true
				},
				
			},
			
			highlight: function(element){
				$(element).closest('.input-group').addClass('validate-has-error');
			},
			
			
			unhighlight: function(element)
			{
				$(element).closest('.input-group').removeClass('validate-has-error');
			},
			
			submitHandler: function(ev)
			{
				/* 
					Login form now processes the login data
				*/
				
				$(".login-page").addClass('logging-in'); // This will hide the login form and init the progress bar
                $(".login-main-box").fadeTo(400, 0);
                $(".footer-login-links").fadeTo(400, 0);
				
                // remove any success notifications
                $('.alert-success').hide();
					
				// Hide Errors
				$(".form-login-error").slideUp('fast');

				// We will wait till the transition ends				
				setTimeout(function()
				{
					var random_pct = 25 + Math.round(Math.random() * 30);
					
					// The form data are subbmitted, we can forward the progress to 70%
					flowLogin.setPercentage(40 + random_pct);

					// Send data to the server
					$.ajax({
						url: ACCOUNT_WEB_ROOT + '/ajax/login',
						method: 'POST',
						dataType: 'json',
						data: $('#form_login').serialize(),
						error: function(jqXHR, textStatus)
						{
							alert("An error occoured reaching the site to login, please try again later. Actual response message: "+jqXHR.statusText);
						},
						success: function(response)
						{
							// Login status [success|invalid]
							var login_status = response.login_status;
															
							// Form is fully completed, we update the percentage
							flowLogin.setPercentage(100);
							
							// We will give some time for the animation to finish, then execute the following procedures	
							setTimeout(function()
							{
								// If login is invalid, we store the 
								if(login_status == 'invalid')
								{
									$(".login-page").removeClass('logging-in');
									flowLogin.resetProgressBar(true);
                                                                        $("#error-message-container").html(response.error);
                                                                        if(typeof(grecaptcha) !== "undefined") {
                                                                            grecaptcha.reset();
                                                                        }
								}
								else if(login_status == 'success')
								{
									// Redirect to login page
									setTimeout(function()
									{
										var redirect_url = ACCOUNT_WEB_ROOT;
										
										if(response.redirect_url && response.redirect_url.length)
										{
											redirect_url = response.redirect_url;
										}
										
										window.location.href = redirect_url;
									}, 200);
								}
								
							}, 1000);
						}
					});
						
					
				}, 650);
			}
		});

		// Login Form Setup
		flowLogin.$body = $(".login-page");
		flowLogin.$login_progressbar_indicator = $(".login-progressbar-indicator h3");
		flowLogin.$login_progressbar = flowLogin.$body.find(".login-progressbar div");
		
		flowLogin.$login_progressbar_indicator.html('0%');
		
		if(flowLogin.$body.hasClass('login-form-fall'))
		{
			var focus_set = false;
			
			setTimeout(function(){ 
				flowLogin.$body.addClass('login-form-fall-init')
				
				setTimeout(function()
				{
					if( !focus_set)
					{
						flowLogin.$container.find('input:first').focus();
						focus_set = true;
					}
					
				}, 550);
				
			}, 0);
		}
		else
		{
			flowLogin.$container.find('input:first').focus();
		}
		$('#username').focus();
        
		// Focus Class
		flowLogin.$container.find('.form-control').each(function(i, el)
		{
			var $this = $(el),
				$group = $this.closest('.input-group');
			
			$this.prev('.input-group-addon').click(function()
			{
				$this.focus();
			});
			
			$this.on({
				focus: function()
				{
					$group.addClass('focused');
				},
				
				blur: function()
				{
					$group.removeClass('focused');
				}
			});
		});
		
		// Functions
		$.extend(flowLogin, {
			setPercentage: function(pct, callback)
			{
				pct = parseInt(pct / 100 * 100, 10) + '%';

				// Normal Login
				flowLogin.$login_progressbar_indicator.html(pct);
				flowLogin.$login_progressbar.width(pct);
				
				var o = {
					pct: parseInt(flowLogin.$login_progressbar.width() / flowLogin.$login_progressbar.parent().width() * 100, 10)
				};
				
				TweenMax.to(o, .7, {
					pct: parseInt(pct, 10),
					roundProps: ["pct"],
					ease: Sine.easeOut,
					onUpdate: function()
					{
						flowLogin.$login_progressbar_indicator.html(o.pct + '%');
					},
					onComplete: callback
				});
			},
			
			resetProgressBar: function(display_errors)
			{
				TweenMax.set(flowLogin.$container.parents('.login-main-box'), {css: {opacity: 0}});
				
				setTimeout(function()
				{
					TweenMax.to(flowLogin.$container.parents('.login-main-box'), .6, {css: {opacity: 1}, onComplete: function()
					{
					    $(".footer-login-links").fadeTo('fast', 1);
						flowLogin.$container.attr('style', '');
					}});
					
					flowLogin.$login_progressbar_indicator.html('0%');
					flowLogin.$login_progressbar.width(0);
					
					if(display_errors)
					{
						var $errors_container = $(".form-login-error");
						
						$errors_container.show();
						var height = $errors_container.outerHeight();
						
						$errors_container.css({
							height: 0
						});
						
						TweenMax.to($errors_container, .45, {css: {height: height}, onComplete: function()
						{
							$errors_container.css({height: 'auto'});
						}});
						
						// Reset password fields
						flowLogin.$container.find('input[type="password"]').val('');
					}
					
				}, 800);
			}
		});
	});
	
})(jQuery, window);


var flowForgotPassword = flowForgotPassword || {};

;(function($, window, undefined)
{
	"use strict";
	
	$(document).ready(function()
	{
		flowForgotPassword.$container = $("#form_forgot_password");
		
		// forgot password & validation
		flowForgotPassword.$container.validate({
			rules: {
				emailAddress: {
					required: true	
				}
			},
			
			highlight: function(element){
				$(element).closest('.input-group').addClass('validate-has-error');
			},
			
			unhighlight: function(element)
			{
				$(element).closest('.input-group').removeClass('validate-has-error');
			},
			
			submitHandler: function(ev)
			{
				$(".login-page").addClass('logging-in'); // This will hide the login form and init the progress bar
                $(".login-main-box").fadeTo('fast', 0);
                $(".footer-login-links").fadeTo('fast', 0);

                // remove any success notifications
                $('.alert-success').hide();
                
				// Hide Errors
				$(".form-login-error").slideUp('fast');

				// We will wait till the transition ends				
				setTimeout(function()
				{
                                    var random_pct = 25 + Math.round(Math.random() * 30);

                                    // The form data are subbmitted, we can forward the progress to 70%
                                    flowForgotPassword.setPercentage(40 + random_pct);

                                    // Send data to the server
                                    $.ajax({
                                        url: ACCOUNT_WEB_ROOT + '/ajax/forgot_password',
                                        method: 'POST',
                                        dataType: 'json',
                                        data: {
                                            emailAddress: $("input#emailAddress").val()
                                        },
                                        error: function()
                                        {
                                            alert("An error occoured reaching the site, please try again later.");
                                        },
                                        success: function(response)
                                        {
                                            // Login status [success|invalid]
                                            var forgot_password_status = response.forgot_password_status;

                                            // Form is fully completed, we update the percentage
                                            flowForgotPassword.setPercentage(100);

                                            // We will give some time for the animation to finish, then execute the following procedures	
                                            setTimeout(function()
                                            {
                                                // If login is invalid, we store the 
                                                if(forgot_password_status == 'invalid')
                                                {
                                                    $(".login-page").removeClass('logging-in');
                                                    flowForgotPassword.resetProgressBar(true);
                                                    $("#error-message-container").html(response.error);
                                                }
                                                else if(forgot_password_status == 'success')
                                                {
                                                    // Redirect to login page
                                                    setTimeout(function()
                                                    {
                                                        var redirect_url = ACCOUNT_WEB_ROOT;

                                                        if(response.redirect_url && response.redirect_url.length)
                                                        {
                                                            redirect_url = response.redirect_url;
                                                        }

                                                        window.location.href = redirect_url;
                                                    }, 200);
                                                }

                                            }, 1000);
                                        }
                                    });

				}, 650);
			}
		});

		// Login Form Setup
		flowForgotPassword.$body = $(".login-page");
		flowForgotPassword.$login_progressbar_indicator = $(".login-progressbar-indicator h3");
		flowForgotPassword.$login_progressbar = flowForgotPassword.$body.find(".login-progressbar div");
		
		flowForgotPassword.$login_progressbar_indicator.html('0%');
		
		if(flowForgotPassword.$body.hasClass('login-form-fall'))
		{
			var focus_set = false;
			
			setTimeout(function(){ 
				flowForgotPassword.$body.addClass('login-form-fall-init')
				
				setTimeout(function()
				{
					if( !focus_set)
					{
						flowForgotPassword.$container.find('input:first').focus();
						focus_set = true;
					}
					
				}, 550);
				
			}, 0);
		}
		else
		{
			flowForgotPassword.$container.find('input:first').focus();
		}
		
		// Focus Class
		flowForgotPassword.$container.find('.form-control').each(function(i, el)
		{
			var $this = $(el),
				$group = $this.closest('.input-group');
			
			$this.prev('.input-group-addon').click(function()
			{
				$this.focus();
			});
			
			$this.on({
				focus: function()
				{
					$group.addClass('focused');
				},
				
				blur: function()
				{
					$group.removeClass('focused');
				}
			});
		});
		
		// Functions
		$.extend(flowForgotPassword, {
			setPercentage: function(pct, callback)
			{
				pct = parseInt(pct / 100 * 100, 10) + '%';
				
				// Normal Login
				flowForgotPassword.$login_progressbar_indicator.html(pct);
				flowForgotPassword.$login_progressbar.width(pct);
				
				var o = {
					pct: parseInt(flowForgotPassword.$login_progressbar.width() / flowForgotPassword.$login_progressbar.parent().width() * 100, 10)
				};
				
				TweenMax.to(o, .7, {
					pct: parseInt(pct, 10),
					roundProps: ["pct"],
					ease: Sine.easeOut,
					onUpdate: function()
					{
						flowForgotPassword.$login_progressbar_indicator.html(o.pct + '%');
					},
					onComplete: callback
				});
			},
			
			resetProgressBar: function(display_errors)
			{
				TweenMax.set(flowForgotPassword.$container.parents('.login-main-box'), {css: {opacity: 0}});
				
				setTimeout(function()
				{
					TweenMax.to(flowForgotPassword.$container.parents('.login-main-box'), .6, {css: {opacity: 1}, onComplete: function()
					{
					    $(".footer-login-links").fadeTo('fast', 1);
						flowForgotPassword.$container.attr('style', '');
					}});
					
					flowForgotPassword.$login_progressbar_indicator.html('0%');
					flowForgotPassword.$login_progressbar.width(0);
					
					if(display_errors)
					{
						var $errors_container = $(".form-login-error");
						
						$errors_container.show();
						var height = $errors_container.outerHeight();
						
						$errors_container.css({
							height: 0
						});
						
						TweenMax.to($errors_container, .45, {css: {height: height}, onComplete: function()
						{
							$errors_container.css({height: 'auto'});
						}});
						
						// Reset password fields
						flowForgotPassword.$container.find('input[type="password"]').val('');
					}
					
				}, 800);
			}
		});
	});
	
})(jQuery, window);


var flowForgotPasswordReset = flowForgotPasswordReset || {};

;(function($, window, undefined)
{
	"use strict";
	
	$(document).ready(function()
	{
		flowForgotPasswordReset.$container = $("#form_forgot_password_reset");
		
		// forgot password reset & validation
		flowForgotPasswordReset.$container.validate({
			rules: {
				emailAddress: {
					required: true	
				}
			},
			
			highlight: function(element){
				$(element).closest('.input-group').addClass('validate-has-error');
			},
			
			unhighlight: function(element)
			{
				$(element).closest('.input-group').removeClass('validate-has-error');
			},
			
			submitHandler: function(ev)
			{
				$(".login-page").addClass('logging-in'); // This will hide the login form and init the progress bar
                $(".login-main-box").fadeTo(400, 0);
                $(".footer-login-links").fadeTo(400, 0);

                // remove any success notifications
                $('.alert-success').hide();
                
				// Hide Errors
				$(".form-login-error").slideUp('fast');

				// We will wait till the transition ends				
				setTimeout(function()
				{
					var random_pct = 25 + Math.round(Math.random() * 30);
					
					// The form data are subbmitted, we can forward the progress to 70%
					flowForgotPasswordReset.setPercentage(40 + random_pct);
			
					// Send data to the server
					$.ajax({
						url: ACCOUNT_WEB_ROOT + '/ajax/forgot_password_reset',
						method: 'POST',
						dataType: 'json',
						data: {
                                                    password: $("input#password").val(),
                                                    confirmPassword: $("input#confirmPassword").val(),
                                                    u: $("input#u").val(),
                                                    h: $("input#h").val()
						},
						error: function()
						{
                                                    alert("An error occoured reaching the site, please try again later.");
						},
                                                success: function (response)
                                                {
                                                    // Login status [success|invalid]
                                                    var forgot_password_status = response.forgot_password_status;

                                                    // Form is fully completed, we update the percentage
                                                    flowForgotPasswordReset.setPercentage(100);

                                                    // We will give some time for the animation to finish, then execute the following procedures	
                                                    setTimeout(function ()
                                                    {
                                                        // If login is invalid, we store the 
                                                        if (forgot_password_status == 'invalid')
                                                        {
                                                            $(".login-page").removeClass('logging-in');
                                                            flowForgotPasswordReset.resetProgressBar(true);
                                                            $("#error-message-container").html(response.error);
                                                        } else if (forgot_password_status == 'success')
                                                        {
                                                            // Redirect to login page
                                                            setTimeout(function ()
                                                            {
                                                                var redirect_url = ACCOUNT_WEB_ROOT;

                                                                if (response.redirect_url && response.redirect_url.length)
                                                                {
                                                                    redirect_url = response.redirect_url;
                                                                }

                                                                window.location.href = redirect_url;
                                                            }, 200);
                                                        }

                                                    }, 1000);
                                                }
					});
						
					
				}, 650);
			}
		});

		// Login Form Setup
		flowForgotPasswordReset.$body = $(".login-page");
		flowForgotPasswordReset.$login_progressbar_indicator = $(".login-progressbar-indicator h3");
		flowForgotPasswordReset.$login_progressbar = flowForgotPasswordReset.$body.find(".login-progressbar div");
		
		flowForgotPasswordReset.$login_progressbar_indicator.html('0%');
		
		if(flowForgotPasswordReset.$body.hasClass('login-form-fall'))
		{
			var focus_set = false;
			
			setTimeout(function(){ 
				flowForgotPasswordReset.$body.addClass('login-form-fall-init')
				
				setTimeout(function()
				{
					if( !focus_set)
					{
						flowForgotPasswordReset.$container.find('input:first').focus();
						focus_set = true;
					}
					
				}, 550);
				
			}, 0);
		}
		else
		{
			flowForgotPasswordReset.$container.find('input:first').focus();
		}
		
		// Focus Class
		flowForgotPasswordReset.$container.find('.form-control').each(function(i, el)
		{
			var $this = $(el),
				$group = $this.closest('.input-group');
			
			$this.prev('.input-group-addon').click(function()
			{
				$this.focus();
			});
			
			$this.on({
				focus: function()
				{
					$group.addClass('focused');
				},
				
				blur: function()
				{
					$group.removeClass('focused');
				}
			});
		});
		
		// Functions
		$.extend(flowForgotPasswordReset, {
			setPercentage: function(pct, callback)
			{
				pct = parseInt(pct / 100 * 100, 10) + '%';
				
				// Normal Login
				flowForgotPasswordReset.$login_progressbar_indicator.html(pct);
				flowForgotPasswordReset.$login_progressbar.width(pct);
				
				var o = {
					pct: parseInt(flowForgotPasswordReset.$login_progressbar.width() / flowForgotPasswordReset.$login_progressbar.parent().width() * 100, 10)
				};
				
				TweenMax.to(o, .7, {
					pct: parseInt(pct, 10),
					roundProps: ["pct"],
					ease: Sine.easeOut,
					onUpdate: function()
					{
						flowForgotPasswordReset.$login_progressbar_indicator.html(o.pct + '%');
					},
					onComplete: callback
				});
			},
			
			resetProgressBar: function(display_errors)
			{
				TweenMax.set(flowForgotPasswordReset.$container.parents('.login-main-box'), {css: {opacity: 0}});
				
				setTimeout(function()
				{
					TweenMax.to(flowForgotPasswordReset.$container.parents('.login-main-box'), .6, {css: {opacity: 1}, onComplete: function()
					{
					     $(".footer-login-links").fadeTo('fast', 1);
						flowForgotPasswordReset.$container.attr('style', '');
					}});
					
					flowForgotPasswordReset.$login_progressbar_indicator.html('0%');
					flowForgotPasswordReset.$login_progressbar.width(0);
					
					if(display_errors)
					{
						var $errors_container = $(".form-login-error");
						
						$errors_container.show();
						var height = $errors_container.outerHeight();
						
						$errors_container.css({
							height: 0
						});
						
						TweenMax.to($errors_container, .45, {css: {height: height}, onComplete: function()
						{
							$errors_container.css({height: 'auto'});
						}});
						
						// Reset password fields
						flowForgotPasswordReset.$container.find('input[type="password"]').val('');
					}
					
				}, 800);
			}
		});
	});
	
})(jQuery, window);

<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if ($this->session->userdata('email')) {
            redirect('user');
        }
        $this->form_validation->set_rules('email', 'Email', 'trim|required');
        $this->form_validation->set_rules('password', 'Password', 'trim|required');

        if ($this->form_validation->run() == false) {
            $data['title'] = 'Login Page';
            $this->load->view('templates/header', $data);
            $this->load->view('auth/login');
            $this->load->view('templates/footer');
        } else {
            $this->_login();
        }
    }

    private function _login()
    {
        $email = $this->input->post('email');
        $password = $this->input->post('password');
        $user = $this->db->get_where('users', ['email' => $email])->row_array();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $data = ['email' => $user['email']];
                $this->session->set_userdata($data);
                redirect('user');
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert"> Wrong password!</div>');
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Email is not registered!</div>');
            redirect('auth');
        }
        if ($this->form_validation->run() == false) {
            $data['title'] = 'Form Login';
            $this->load->view('templates/header', $data);
            $this->load->view('auth/login');
            $this->load->view('templates/footer');
        }
    }

    public function logout()
    {
        $this->session->unset_userdata('email');
        $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">You have been logout!</div>');
        redirect('auth');
    }

    public function registration()
    {
        if ($this->session->userdata('email')) {
            redirect('user');
        }

        $this->form_validation->set_rules('name', 'Name', 'required|trim');
        $this->form_validation->set_rules('email', 'Email', 'required|trim|is_unique[users.email]|valid_email', [
            'is_unique' => 'This email has already registered!'
        ]);
        $this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[3]|matches[password2]', [
            'min_length' => 'Password too short!',
            'matches' => 'Password doesn\'t match!',
            'required' => 'Password is required'
        ]);
        $this->form_validation->set_rules('password2', 'Password', 'required|trim|matches[password1]', [
            'required' => 'Password is required'
        ]);
        $this->form_validation->set_rules('image', 'Image', 'trim');

        if ($this->form_validation->run() == false) {
            $data['title'] = 'Registration Page';
            $this->load->view('templates/header', $data);
            $this->load->view('auth/registration');
            $this->load->view('templates/footer');
        } else {
            $email = $this->input->post('email', true);
            $data = [
                'name' => htmlspecialchars($this->input->post('name', true)),
                'email' => $email,
                'password' => password_hash($this->input->post('password1'), PASSWORD_DEFAULT),
                'image' => 'undraw_profile.svg',
                'date_created' => date('Ymd')
            ];

            $this->db->insert('users', $data);
            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Successfully to Registered!, Please activated your account!</div>');
            redirect('auth');
        }
    }

    public function forgot_password()
    {
        if ($this->session->userdata('email')) {
            redirect('user');
        }

        $this->form_validation->set_rules('email', 'Email', 'required|trim');

        if ($this->form_validation->run() == false) {
            $data['title'] = 'Forgot password';
            $this->load->view('templates/header', $data);
            $this->load->view('auth/forgot-password');
            $this->load->view('templates/footer');
        } else {
            $email = $this->input->post('email');
            $user = $this->db->get_where('users', ['email' => $email])->row_array();

            if ($user) {
                $this->session->set_userdata('forgot_password', $email);
                $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Please reset your password!</div>');
                $this->change_password();
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Email account does\'n exist!</div>');
                redirect('auth/forgot_password');
            }
        }
    }

    public function change_password()
    {
        if (!$this->session->userdata('forgot_password')) {
            redirect('auth');
        } else if ($this->session->userdata('email')) {
            redirect('user');
        }

        $this->form_validation->set_rules('password1', 'Password', 'trim|required|min_length[3]|matches[password2]');
        $this->form_validation->set_rules('password2', 'Password', 'trim|required|min_length[3]|matches[password1]');

        if ($this->form_validation->run() == false) {
            $data['title'] = 'Change Password';
            $this->load->view('templates/header', $data);
            $this->load->view('auth/change-password');
            $this->load->view('templates/footer');
        } else {
            $password = password_hash($this->input->post('password1'), PASSWORD_DEFAULT);
            $email = $this->session->userdata('forgot_password');

            $this->db->set('password', $password);
            $this->db->where('email', $email);
            $this->db->update('users');

            $this->session->unset_userdata('forgot_password');

            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Password has been changed, plese login!</div>');
            redirect('auth');
        }
    }
}
